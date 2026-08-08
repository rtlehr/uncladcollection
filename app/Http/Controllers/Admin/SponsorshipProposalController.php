<?php

namespace App\Http\Controllers\Admin;

use App\Advertising\AdvertisingWorkflowContextService;
use App\Http\Controllers\Controller;
use App\Models\{AdPlacement, Advertiser, SponsorshipLead, SponsorshipPackage, SponsorshipProposal};
use App\Services\SponsorshipProposalConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SponsorshipProposalController extends Controller
{
    public function index() { return Inertia::render('Admin/Sponsorship/Proposals/Index', ['proposals' => SponsorshipProposal::with(['advertiser','lead','package'])->latest()->get()]); }
    public function create(Request $request, AdvertisingWorkflowContextService $context) { $package=$request->filled('package')?SponsorshipPackage::with('placements')->find($request->integer('package')):null; $workflowContext=$context->fromRequest($request); return Inertia::render('Admin/Sponsorship/Proposals/Form',['proposal'=>null,'selectedPackage'=>$package,'packages'=>SponsorshipPackage::where('is_active',true)->orderBy('name')->get(),'leads'=>SponsorshipLead::whereNotIn('stage',['lost','won'])->orderBy('company_name')->get(),'advertisers'=>Advertiser::orderBy('name')->get(),'placements'=>AdPlacement::where('is_active',true)->orderBy('name')->get(),'workflowContext'=>$workflowContext,'selectedAdvertiserId'=>$workflowContext['advertiser']['id']??null,'selectedLeadId'=>$workflowContext['lead']['id']??null]); }
    public function store(Request $request) { $data=$this->data($request); $proposal=SponsorshipProposal::create(array_merge($data,['uuid'=>(string)Str::uuid(),'proposal_number'=>'SP-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)),'created_by'=>$request->user()->id,'status'=>'draft'])); $this->replaceItems($proposal,$request); $this->history($request,$proposal,null,'draft','Proposal created.'); return to_route('admin.sponsorship-proposals.show',$proposal)->with('success','Proposal created. Next step: review it and mark it sent.'); }
    public function show(SponsorshipProposal $sponsorshipProposal, AdvertisingWorkflowContextService $context) { $proposal=$sponsorshipProposal->load(['advertiser','lead','package','items.placement','campaign','invoice','acceptance.user','statusHistory.user']); return Inertia::render('Admin/Sponsorship/Proposals/Show',['proposal'=>$proposal,'workflowContext'=>$context->payload($proposal->advertiser,$proposal->lead,$proposal->campaign),'nextStep'=>$this->nextStep($proposal)]); }
    public function edit(SponsorshipProposal $sponsorshipProposal, AdvertisingWorkflowContextService $context) { abort_if(in_array($sponsorshipProposal->status,['accepted','converted']),422); $sponsorshipProposal->load(['items','advertiser','lead']); return Inertia::render('Admin/Sponsorship/Proposals/Form',['proposal'=>$sponsorshipProposal,'selectedPackage'=>null,'packages'=>SponsorshipPackage::where('is_active',true)->orderBy('name')->get(),'leads'=>SponsorshipLead::orderBy('company_name')->get(),'advertisers'=>Advertiser::orderBy('name')->get(),'placements'=>AdPlacement::where('is_active',true)->orderBy('name')->get(),'workflowContext'=>$context->payload($sponsorshipProposal->advertiser,$sponsorshipProposal->lead),'selectedAdvertiserId'=>$sponsorshipProposal->advertiser_id,'selectedLeadId'=>$sponsorshipProposal->sponsorship_lead_id]); }
    public function update(Request $request,SponsorshipProposal $sponsorshipProposal) { abort_if(in_array($sponsorshipProposal->status,['accepted','converted']),422); $sponsorshipProposal->update($this->data($request)); $this->replaceItems($sponsorshipProposal,$request); return to_route('admin.sponsorship-proposals.show',$sponsorshipProposal)->with('success','Proposal updated. Continue with the next workflow step shown below.'); }
    public function status(Request $request,SponsorshipProposal $sponsorshipProposal) { $data=$request->validate(['status'=>'required|in:sent,accepted,declined,draft','reason'=>'nullable|string|max:2000']); $from=$sponsorshipProposal->status; $status=$data['status']; $updates=['status'=>$status]; if($status==='sent')$updates['sent_at']=now(); if($status==='accepted')$updates['accepted_at']=now(); if($status==='declined')$updates['declined_at']=now(); $sponsorshipProposal->update($updates); $this->history($request,$sponsorshipProposal,$from,$status,$data['reason']??null); $message=match($status){'sent'=>'Proposal marked sent. Next step: record the advertiser’s decision.','accepted'=>'Proposal accepted. Next step: convert it to a campaign.','declined'=>'Proposal marked declined. Review the proposal or sales opportunity before continuing.',default=>'Proposal returned to draft.'}; return back()->with('success',$message); }
    public function convert(Request $request,SponsorshipProposal $sponsorshipProposal,SponsorshipProposalConversionService $service) { $from=$sponsorshipProposal->status; $result=$service->convert($sponsorshipProposal); $sponsorshipProposal->refresh(); $this->history($request,$sponsorshipProposal,$from,'converted','Converted to advertising campaign and invoice.'); return to_route('admin.ad-campaigns.show',$result['campaign'])->with('success','Proposal converted. Advertiser, proposal, campaign, and billing context have been carried forward.'); }
    private function history(Request $request,SponsorshipProposal $proposal,?string $from,string $to,?string $reason):void { $proposal->statusHistory()->create(['user_id'=>$request->user()?->id,'from_status'=>$from,'to_status'=>$to,'reason'=>$reason,'source'=>'admin','ip_address'=>$request->ip(),'user_agent'=>$request->userAgent()]); }
    private function data(Request $request):array { return $request->validate(['sponsorship_lead_id'=>'nullable|exists:sponsorship_leads,id','advertiser_id'=>'required|exists:advertisers,id','sponsorship_package_id'=>'nullable|exists:sponsorship_packages,id','title'=>'required|string|max:255','starts_on'=>'required|date','ends_on'=>'required|date|after_or_equal:starts_on','expires_on'=>'nullable|date','currency'=>'required|string|size:3','discount_cents'=>'required|integer|min:0','tax_cents'=>'required|integer|min:0','terms'=>'nullable|string','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.description'=>'required|string|max:255','items.*.ad_placement_id'=>'nullable|exists:ad_placements,id','items.*.billing_model'=>'required|in:flat,cpm,cpc,sponsorship','items.*.quantity'=>'required|integer|min:1','items.*.unit_amount_cents'=>'required|integer|min:0']); }
    private function replaceItems(SponsorshipProposal $proposal,Request $request):void { $proposal->items()->delete(); $subtotal=0; foreach($request->input('items',[]) as $item){$item['line_total_cents']=(int)$item['quantity']*(int)$item['unit_amount_cents'];$subtotal+=$item['line_total_cents'];$proposal->items()->create($item);} $proposal->update(['subtotal_cents'=>$subtotal,'total_cents'=>max(0,$subtotal-(int)$proposal->discount_cents+(int)$proposal->tax_cents)]); }

    private function nextStep(SponsorshipProposal $proposal): array
    {
        $base = "/admin/sponsorship-proposals/{$proposal->id}";

        return match ($proposal->status) {
            'draft' => [
                'eyebrow' => 'Proposal workflow · Step 1 of 3',
                'title' => 'Send the proposal',
                'description' => 'Review the draft. When it has been delivered to the advertiser, mark it sent. The advertiser and sales-opportunity context will stay attached.',
                'status' => 'current',
                'action' => ['label' => 'Mark Sent', 'href' => "{$base}/status", 'method' => 'post', 'data' => ['status' => 'sent']],
                'secondary' => ['label' => 'Edit Proposal', 'href' => "{$base}/edit"],
            ],
            'sent' => [
                'eyebrow' => 'Proposal workflow · Step 2 of 3',
                'title' => 'Record the advertiser decision',
                'description' => 'The proposal is recorded as sent. When the advertiser accepts it, record the acceptance here so the workflow can continue.',
                'status' => 'waiting',
                'action' => ['label' => 'Accept Manually', 'href' => "{$base}/status", 'method' => 'post', 'data' => ['status' => 'accepted']],
                'secondary' => $proposal->advertiser ? ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$proposal->advertiser->id}"] : null,
            ],
            'accepted' => [
                'eyebrow' => 'Proposal workflow · Step 3 of 3',
                'title' => 'Convert the accepted proposal to a campaign',
                'description' => 'Conversion creates the advertising campaign and invoice from this proposal and keeps the advertiser relationship attached, avoiding duplicate data entry.',
                'status' => 'current',
                'action' => ['label' => 'Convert to Campaign', 'href' => "{$base}/convert", 'method' => 'post', 'data' => []],
                'secondary' => $proposal->advertiser ? ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$proposal->advertiser->id}"] : null,
            ],
            'converted' => [
                'eyebrow' => 'Proposal workflow complete',
                'title' => 'Continue with campaign setup',
                'description' => 'The proposal has been converted. Continue with the linked campaign; the same advertiser context remains active.',
                'status' => 'complete',
                'action' => $proposal->campaign ? ['label' => 'Open Campaign', 'href' => "/admin/ad-campaigns/{$proposal->campaign->id}", 'method' => 'get'] : null,
                'secondary' => $proposal->advertiser ? ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$proposal->advertiser->id}"] : null,
            ],
            'declined' => [
                'eyebrow' => 'Proposal workflow',
                'title' => 'Review the declined proposal',
                'description' => 'The proposal was declined. You can revise it or return to the advertiser workspace before deciding how to proceed.',
                'status' => 'attention',
                'action' => ['label' => 'Edit Proposal', 'href' => "{$base}/edit", 'method' => 'get'],
                'secondary' => $proposal->advertiser ? ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$proposal->advertiser->id}"] : null,
            ],
            default => [
                'title' => 'Review proposal status',
                'description' => 'Review this proposal before continuing the advertiser workflow.',
                'status' => 'current',
                'action' => $proposal->advertiser ? ['label' => 'Client Workspace', 'href' => "/admin/advertisers/{$proposal->advertiser->id}", 'method' => 'get'] : null,
            ],
        };
    }
}
