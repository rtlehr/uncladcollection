<?php

namespace App\Services\PublicPages;

use App\Models\PublicPage;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use JsonException;

class PublicPageTransferService
{
    public const FORMAT='unclad-public-pages'; public const VERSION=1;
    public function exportPayload(): array
    {
        $entries=PublicPage::with(['faqItems','parent'])->orderBy('sort_order')->orderBy('title')->get()->map(fn(PublicPage $p)=>[
            'title'=>$p->title,'slug'=>$p->slug,'parent_slug'=>$p->parent?->slug,'eyebrow'=>$p->eyebrow,'introduction'=>$p->introduction,'content'=>$p->content,
            'page_type'=>$p->page_type,'status'=>$p->status,'is_active'=>$p->is_active,'published_at'=>$p->published_at?->toIso8601String(),
            'navigation_label'=>$p->navigation_label,'navigation_locations'=>$p->navigation_locations??[],'sort_order'=>$p->sort_order,
            'seo_title'=>$p->seo_title,'seo_description'=>$p->seo_description,'canonical_url'=>$p->canonical_url,
            'header_image_original_path'=>$p->header_image_original_path,'header_image_path'=>$p->header_image_path,'header_image_edit'=>$p->header_image_edit,
            'header_image_alt'=>$p->header_image_alt,'legal_version'=>$p->legal_version,'effective_date'=>$p->effective_date?->toDateString(),
            'revised_date'=>$p->revised_date?->toDateString(),'faq_items'=>$p->faqItems->map->only(['question','answer','is_active','sort_order'])->values()->all(),
        ])->all();
        return ['format'=>self::FORMAT,'version'=>self::VERSION,'exported_at'=>now()->toIso8601String(),'entry_count'=>count($entries),'entries'=>$entries];
    }
    public function exportJson(): string { return json_encode($this->exportPayload(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL; }
    public function decode(string $json): array
    {
        try{$payload=json_decode($json,true,512,JSON_THROW_ON_ERROR);}catch(JsonException $e){throw new InvalidArgumentException('The selected file does not contain valid JSON.',previous:$e);}
        $v=Validator::make($payload??[],['format'=>['required',Rule::in([self::FORMAT])],'version'=>['required','integer',Rule::in([self::VERSION])],'entries'=>['required','array'],'entries.*.title'=>['required','string','max:255'],'entries.*.slug'=>['required','alpha_dash','max:255'],'entries.*.parent_slug'=>['nullable','alpha_dash','max:255'],'entries.*.page_type'=>['required',Rule::in(array_keys(config('public-pages.types',[])))],'entries.*.status'=>['required',Rule::in(['draft','published'])],'entries.*.is_active'=>['required','boolean'],'entries.*.faq_items'=>['present','array']]);
        if($v->fails()) throw new InvalidArgumentException($v->errors()->first()); return $payload;
    }
    public function importJson(string $json,string $mode='merge',bool $dryRun=false): array
    {
        $payload=$this->decode($json); if(!in_array($mode,['merge','replace'],true)) throw new InvalidArgumentException('Import mode must be merge or replace.');
        $summary=['mode'=>$mode,'created'=>0,'updated'=>0,'unchanged'=>0,'deleted'=>0,'dry_run'=>$dryRun];
        $work=function()use($payload,$mode,$dryRun,&$summary){$slugs=[]; foreach($payload['entries'] as $entry){$slugs[]=$entry['slug'];$existing=PublicPage::with(['faqItems','parent'])->where('slug',$entry['slug'])->first();$parentSlug=$entry['parent_slug']??null;$attrs=Arr::except($entry,['faq_items','parent_slug']);$attrs['published_at']=$entry['published_at']?Carbon::parse($entry['published_at']):null;$existingParentSlug=$existing?->parent?->slug;$changed=!$existing||$existingParentSlug!==$parentSlug||collect($attrs)->contains(fn($v,$k)=>$existing->{$k}!=$v)||($existing?->faqItems->map->only(['question','answer','is_active','sort_order'])->values()->all()!==$entry['faq_items']);$summary[$existing?($changed?'updated':'unchanged'):'created']++;if($dryRun)continue;$page=PublicPage::updateOrCreate(['slug'=>$entry['slug']],$attrs);$page->faqItems()->delete();foreach($entry['faq_items'] as $faq)$page->faqItems()->create($faq);}if(!$dryRun){foreach($payload['entries'] as $entry){$parentSlug=$entry['parent_slug']??null;$parentId=$parentSlug?PublicPage::where('slug',$parentSlug)->value('id'):null;PublicPage::where('slug',$entry['slug'])->update(['parent_id'=>$parentId]);}}if($mode==='replace'){$q=PublicPage::whereNotIn('slug',$slugs);$summary['deleted']=$q->count();if(!$dryRun)$q->delete();}};
        $dryRun?$work():DB::transaction($work); return $summary;
    }
}
