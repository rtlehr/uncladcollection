<?php

namespace App\Services;

use App\Models\MessageBox;
use App\Models\MessageBoxView;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MessageBoxService
{
    public function eligible(Request $request, string $path, string $triggerType = 'auto', ?string $triggerKey = null): Collection
    {
        $user = $request->user();
        $visitorToken = $this->visitorToken($request);

        return MessageBox::query()->current()
            ->where('trigger_type', $triggerType)
            ->when($triggerType === 'action', fn ($q) => $q->where('trigger_key', $triggerKey))
            ->orderBy('priority')->orderBy('id')
            ->get()
            ->filter(fn (MessageBox $box) => $box->matchesPath($path))
            ->filter(function (MessageBox $box) use ($user): bool {
                return match ($box->audience) {
                    'guests' => ! $user,
                    'authenticated' => (bool) $user,
                    default => true,
                };
            })
            ->reject(function (MessageBox $box) use ($user, $visitorToken): bool {
                if (! $box->show_once) return false;
                return MessageBoxView::query()->where('message_box_id', $box->id)
                    ->when($user, fn ($q) => $q->where('user_id', $user->id), fn ($q) => $q->where('visitor_token', $visitorToken))
                    ->exists();
            })
            ->values();
    }

    public function visitorToken(Request $request): ?string
    {
        $token = $request->header('X-Message-Visitor');
        return is_string($token) && preg_match('/^[0-9a-f-]{36}$/i', $token) ? $token : null;
    }

    public function payload(MessageBox $box): array
    {
        return [
            'id'=>$box->id,'uuid'=>$box->uuid,'name'=>$box->name,'title'=>$box->title,'body_html'=>$box->body_html,
            'image_url'=>$box->image_url,'presentation'=>$box->presentation,'is_dismissible'=>$box->is_dismissible,
            'buttons'=>$box->buttons ?: [],'form_fields'=>$box->form_fields ?: [],
            'form_submit_label'=>$box->form_submit_label ?: 'Submit','form_success_message'=>$box->form_success_message ?: 'Thank you. Your response has been received.',
        ];
    }
}
