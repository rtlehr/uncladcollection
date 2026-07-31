<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCommunicationSettingsRequest;
use App\Models\CommunicationSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Communications/Settings/Edit', [
            'settings' => CommunicationSetting::current(),
            'fallbacks' => [
                'sender_name' => config('mail.from.name'),
                'sender_email' => config('mail.from.address'),
            ],
        ]);
    }

    public function update(UpdateCommunicationSettingsRequest $request): RedirectResponse
    {
        CommunicationSetting::current()->update([
            ...$request->validated(),
            'updated_by_user_id' => $request->user()?->id,
        ]);

        return back()->with('success', 'Communication settings updated.');
    }
}
