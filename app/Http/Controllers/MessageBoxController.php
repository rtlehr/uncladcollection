<?php

namespace App\Http\Controllers;

use App\Models\MessageBox;
use App\Models\MessageBoxSubmission;
use App\Models\MessageBoxView;
use App\Services\MessageBoxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class MessageBoxController extends Controller
{
    public function eligible(Request $request, MessageBoxService $service): JsonResponse
    {
        $data = $request->validate(['path'=>['required','string','max:1000'],'trigger_type'=>['nullable',Rule::in(['auto','action'])],'trigger_key'=>['nullable','string','max:100']]);
        $type = $data['trigger_type'] ?? 'auto';
        $boxes = $service->eligible($request, $data['path'], $type, $data['trigger_key'] ?? null);
        return response()->json(['messages'=>$boxes->map(fn (MessageBox $box) => $service->payload($box))->all()]);
    }

    public function seen(Request $request, MessageBox $messageBox, MessageBoxService $service): JsonResponse
    {
        $token = $service->visitorToken($request);
        $user = $request->user();
        MessageBoxView::firstOrCreate(
            ['message_box_id'=>$messageBox->id, $user ? 'user_id' : 'visitor_token' => $user?->id ?? $token],
            ['seen_at'=>now()]
        );
        return response()->json(['ok'=>true]);
    }

    public function dismiss(Request $request, MessageBox $messageBox, MessageBoxService $service): JsonResponse
    {
        $token = $service->visitorToken($request); $user = $request->user();
        $view = MessageBoxView::firstOrCreate(
            ['message_box_id'=>$messageBox->id, $user ? 'user_id' : 'visitor_token' => $user?->id ?? $token],
            ['seen_at'=>now()]
        );
        $view->update(['dismissed_at'=>now()]);
        return response()->json(['ok'=>true]);
    }

    public function submit(Request $request, MessageBox $messageBox, MessageBoxService $service): JsonResponse
    {
        $rules = [];
        foreach ($messageBox->form_fields ?: [] as $field) {
            $name = (string) Arr::get($field, 'name');
            if (! $name) continue;
            $type = Arr::get($field, 'type', 'text');
            $fieldRules = [Arr::get($field, 'required') ? 'required' : 'nullable'];
            $fieldRules[] = $type === 'email' ? 'email' : ($type === 'checkbox' ? 'boolean' : 'string');
            $fieldRules[] = $type === 'checkbox' ? null : 'max:5000';
            $rules[$name] = array_values(array_filter($fieldRules));
        }
        $validated = $request->validate($rules);
        MessageBoxSubmission::create(['message_box_id'=>$messageBox->id,'user_id'=>$request->user()?->id,'visitor_token'=>$service->visitorToken($request),'data'=>$validated]);
        $this->seen($request, $messageBox, $service);
        return response()->json(['ok'=>true,'message'=>$messageBox->form_success_message ?: 'Thank you. Your response has been received.']);
    }
}
