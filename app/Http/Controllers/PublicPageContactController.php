<?php

namespace App\Http\Controllers;

use App\Models\PublicPage;
use App\Models\SupportTicketCategory;
use App\Notifications\SupportTicketCreatedNotification;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PublicPageContactController extends Controller
{
    public function __invoke(Request $request, PublicPage $publicPage, SupportTicketService $tickets): RedirectResponse
    {
        abort_unless($publicPage->isPublished() && $publicPage->page_type === PublicPage::TYPE_CONTACT, 404);
        $data=$request->validate(['name'=>['required','string','max:120'],'email'=>['required','email:rfc','max:255'],'subject'=>['required','string','max:180'],'topic'=>['nullable','string','max:120'],'message'=>['required','string','max:10000']]);
        $category=SupportTicketCategory::query()->where('slug','general-question')->where('is_active',true)->first();
        $payload=['category_id'=>$category?->id,'subject'=>$data['subject'],'description'=>trim(($data['topic']?"Topic: {$data['topic']}\n\n":'').$data['message'])];
        if($request->user()) { $ticket=$tickets->createForMember($request->user(),$payload); return back()->with('success',"Thank you. Support ticket {$ticket->ticket_number} was created."); }
        $result=$tickets->createForGuest([...$payload,'guest_name'=>$data['name'],'guest_email'=>$data['email']]);
        Notification::route('mail',[$data['email']=>$data['name']])->notify(new SupportTicketCreatedNotification($result['ticket'],$result['token']));
        return back()->with('success','Thank you. Your message was sent to our support team and a secure tracking link was emailed to you.');
    }
}
