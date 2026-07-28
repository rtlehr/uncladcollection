<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\Licenses\LicenseDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseDocumentController extends Controller
{
    public function certificate(Request $request, License $license, LicenseDocumentService $documents): Response
    {
        $this->authorizeOwner($request, $license);
        return $this->download($documents->certificate($license), 'license-certificate-'.$license->license_key.'.pdf');
    }

    public function proofOfPurchase(Request $request, License $license, LicenseDocumentService $documents): Response
    {
        $this->authorizeOwner($request, $license);
        return $this->download($documents->proofOfPurchase($license), 'proof-of-purchase-'.$license->license_key.'.pdf');
    }

    private function authorizeOwner(Request $request, License $license): void
    {
        abort_unless((int) $license->user_id === (int) $request->user()->id, 403);
    }

    private function download(string $content, string $filename): Response
    {
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
