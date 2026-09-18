<?php

namespace App\Services\Social;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class XApiClient
{
    private string $apiKey;
    private string $apiSecret;
    private string $accessToken;
    private string $accessTokenSecret;
    private string $apiBase;
    private string $uploadBase;

    public function __construct()
    {
        $this->apiKey = (string) config('services.x.api_key');
        $this->apiSecret = (string) config('services.x.api_secret');
        $this->accessToken = (string) config('services.x.access_token');
        $this->accessTokenSecret = (string) config('services.x.access_token_secret');
        $this->apiBase = rtrim((string) config('services.x.api_base', 'https://api.x.com'), '/');
        $this->uploadBase = rtrim((string) config('services.x.upload_base', 'https://upload.twitter.com'), '/');
    }

    public function configured(): bool
    {
        return $this->apiKey !== ''
            && $this->apiSecret !== ''
            && $this->accessToken !== ''
            && $this->accessTokenSecret !== '';
    }

    public function publish(string $text, ?string $mediaPath = null, ?string $mediaType = null): array
    {
        $this->assertConfigured();

        $mediaId = null;
        if ($mediaPath) {
            if (! is_file($mediaPath)) {
                throw new RuntimeException('The social post media file could not be found.');
            }

            $mediaId = $mediaType === 'video'
                ? $this->uploadVideo($mediaPath)
                : $this->uploadImage($mediaPath);
        }

        $url = $this->apiBase.'/2/tweets';
        $payload = ['text' => $text];
        if ($mediaId) {
            $payload['media'] = ['media_ids' => [(string) $mediaId]];
        }

        $response = $this->jsonRequest('POST', $url, $payload);
        $this->throwForXError($response, 'X rejected the post.');

        $body = $response->json() ?: [];
        $id = data_get($body, 'data.id');
        if (! $id) {
            throw new RuntimeException('X did not return a post ID.');
        }

        return [
            'id' => (string) $id,
            'url' => 'https://x.com/i/web/status/'.$id,
            'response' => $body,
        ];
    }

    public function verifyCredentials(): array
    {
        $this->assertConfigured();
        $url = $this->apiBase.'/2/users/me';
        $response = $this->signedRequest('GET', $url);
        $this->throwForXError($response, 'X credentials could not be verified.');

        return $response->json() ?: [];
    }

    private function uploadImage(string $path): string
    {
        $url = $this->uploadBase.'/1.1/media/upload.json';
        $oauth = $this->oauthHeader('POST', $url);
        $response = Http::timeout(90)
            ->withHeaders(['Authorization' => $oauth, 'Accept' => 'application/json'])
            ->attach('media', file_get_contents($path), basename($path))
            ->post($url);

        $this->throwForXError($response, 'X rejected the image upload.');
        $id = $response->json('media_id_string') ?: $response->json('media_id');
        if (! $id) {
            throw new RuntimeException('X did not return a media ID for the image.');
        }

        return (string) $id;
    }

    private function uploadVideo(string $path): string
    {
        $url = $this->uploadBase.'/1.1/media/upload.json';
        $size = filesize($path);
        $mime = mime_content_type($path) ?: 'video/mp4';

        $init = [
            'command' => 'INIT',
            'total_bytes' => (string) $size,
            'media_type' => $mime,
            'media_category' => 'tweet_video',
        ];
        $response = $this->formRequest('POST', $url, $init);
        $this->throwForXError($response, 'X rejected the video upload initialization.');
        $mediaId = $response->json('media_id_string') ?: $response->json('media_id');
        if (! $mediaId) {
            throw new RuntimeException('X did not return a media ID for the video.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The video file could not be opened.');
        }

        try {
            $segment = 0;
            while (! feof($handle)) {
                $chunk = fread($handle, 4 * 1024 * 1024);
                if ($chunk === false || $chunk === '') {
                    break;
                }

                $params = [
                    'command' => 'APPEND',
                    'media_id' => (string) $mediaId,
                    'segment_index' => (string) $segment,
                ];
                $oauth = $this->oauthHeader('POST', $url);
                $append = Http::timeout(120)
                    ->withHeaders(['Authorization' => $oauth, 'Accept' => 'application/json'])
                    ->attach('media', $chunk, 'segment.bin')
                    ->post($url, $params);
                $this->throwForXError($append, 'X rejected a video upload segment.');
                $segment++;
            }
        } finally {
            fclose($handle);
        }

        $finalize = $this->formRequest('POST', $url, [
            'command' => 'FINALIZE',
            'media_id' => (string) $mediaId,
        ]);
        $this->throwForXError($finalize, 'X rejected the video upload finalization.');

        $processing = $finalize->json('processing_info');
        if ($processing) {
            $this->waitForVideoProcessing($url, (string) $mediaId, $processing);
        }

        return (string) $mediaId;
    }

    private function waitForVideoProcessing(string $url, string $mediaId, array $processing): void
    {
        $attempts = 0;
        while (in_array($processing['state'] ?? null, ['pending', 'in_progress'], true) && $attempts < 20) {
            $seconds = max(1, min(10, (int) ($processing['check_after_secs'] ?? 2)));
            sleep($seconds);
            $response = $this->signedRequest('GET', $url, [
                'command' => 'STATUS',
                'media_id' => $mediaId,
            ]);
            $this->throwForXError($response, 'X could not confirm video processing.');
            $processing = $response->json('processing_info') ?: [];
            $attempts++;
        }

        if (($processing['state'] ?? null) !== 'succeeded') {
            $message = data_get($processing, 'error.message', 'X did not finish processing the video.');
            throw new RuntimeException((string) $message);
        }
    }

    private function jsonRequest(string $method, string $url, array $payload): Response
    {
        $oauth = $this->oauthHeader($method, $url);
        return Http::timeout(90)
            ->withHeaders(['Authorization' => $oauth, 'Accept' => 'application/json'])
            ->asJson()
            ->send($method, $url, ['json' => $payload]);
    }

    private function formRequest(string $method, string $url, array $params): Response
    {
        $oauth = $this->oauthHeader($method, $url, $params);
        return Http::timeout(90)
            ->withHeaders(['Authorization' => $oauth, 'Accept' => 'application/json'])
            ->asForm()
            ->send($method, $url, ['form_params' => $params]);
    }

    private function signedRequest(string $method, string $url, array $query = []): Response
    {
        $oauth = $this->oauthHeader($method, $url, $query);
        return Http::timeout(60)
            ->withHeaders(['Authorization' => $oauth, 'Accept' => 'application/json'])
            ->send($method, $url, ['query' => $query]);
    }

    private function oauthHeader(string $method, string $url, array $requestParams = []): string
    {
        $oauth = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => Str::random(32),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
        ];

        $parts = parse_url($url);
        $baseUrl = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').($parts['path'] ?? '');
        $queryParams = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $queryParams);
        }

        $signatureParams = array_merge($queryParams, $requestParams, $oauth);
        ksort($signatureParams);

        $encoded = [];
        foreach ($signatureParams as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $encoded[] = $this->encode((string) $key).'='.$this->encode((string) $value);
        }

        $baseString = strtoupper($method).'&'.$this->encode($baseUrl).'&'.$this->encode(implode('&', $encoded));
        $key = $this->encode($this->apiSecret).'&'.$this->encode($this->accessTokenSecret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseString, $key, true));

        ksort($oauth);
        return 'OAuth '.implode(', ', array_map(
            fn ($key, $value) => $this->encode((string) $key).'="'.$this->encode((string) $value).'"',
            array_keys($oauth),
            array_values($oauth),
        ));
    }

    private function encode(string $value): string
    {
        return str_replace('%7E', '~', rawurlencode($value));
    }

    private function throwForXError(Response $response, string $fallback): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('detail')
            ?: $response->json('title')
            ?: data_get($response->json(), 'errors.0.message')
            ?: $response->body()
            ?: $fallback;

        throw new RuntimeException($fallback.' '.Str::limit((string) $message, 1000));
    }

    private function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('X API credentials are not configured. Add the X_API_* values to .env.');
        }
    }
}
