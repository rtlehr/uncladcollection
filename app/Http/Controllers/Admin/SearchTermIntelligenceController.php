<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchTerm;
use App\Models\SearchTermMapping;
use App\Services\DiscoveryCacheService;
use App\Services\SearchIntelligence\SearchTermAggregationService;
use App\Services\SearchIntelligence\SearchTermAiService;
use App\Services\SearchIntelligence\SearchTermResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchTermIntelligenceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search'=>['nullable','string','max:120'],'status'=>['nullable','in:,unanalyzed,pending,approved,rejected'],'opportunity'=>['nullable','boolean']]);
        $query = SearchTerm::query()->with(['mapping','variants'=>fn ($q)=>$q->orderByDesc('search_count')->limit(6)]);
        if ($value = trim((string) ($filters['search'] ?? ''))) $query->where(fn ($q)=>$q->where('display_term','like',"%{$value}%")->orWhere('normalized_term','like',"%{$value}%"));
        if (($filters['status'] ?? '') === 'unanalyzed') $query->doesntHave('mapping');
        elseif ($filters['status'] ?? '') $query->whereHas('mapping',fn ($q)=>$q->where('status',$filters['status']));
        if ($request->boolean('opportunity')) $query->where('is_content_opportunity',true);

        return Inertia::render('Admin/Discovery/SearchIntelligence/Index', [
            'filters'=>$filters,
            'terms'=>$query->orderByDesc('search_count')->paginate(30)->withQueryString()->through(fn (SearchTerm $term)=>[
                'id'=>$term->id,'term'=>$term->display_term,'normalized_term'=>$term->normalized_term,
                'search_count'=>$term->search_count,'unique_searchers'=>$term->unique_searchers,
                'zero_result_count'=>$term->zero_result_count,'average_results'=>$term->average_results,
                'click_count'=>$term->click_count,'order_count'=>$term->order_count,'revenue_cents'=>$term->revenue_cents,
                'is_content_opportunity'=>$term->is_content_opportunity,
                'variants'=>$term->variants->map(fn ($v)=>['term'=>$v->raw_term,'count'=>$v->search_count])->all(),
                'mapping'=>$term->mapping ? [
                    'status'=>$term->mapping->status,'suggested_canonical_term'=>$term->mapping->suggested_canonical_term,
                    'approved_canonical_term'=>$term->mapping->approved_canonical_term,'suggested_synonyms'=>$term->mapping->suggested_synonyms ?: [],
                    'approved_synonyms'=>$term->mapping->approved_synonyms ?: [],'intent_category'=>$term->mapping->intent_category,
                    'confidence'=>$term->mapping->confidence,'explanation'=>$term->mapping->ai_explanation,
                ] : null,
            ]),
        ]);
    }

    public function rebuild(SearchTermAggregationService $service): RedirectResponse
    {
        $summary=$service->rebuild();
        return back()->with('success',"Aggregated {$summary['events']} searches into {$summary['terms']} terms.");
    }

    public function analyze(SearchTerm $searchTerm, SearchTermAiService $service): RedirectResponse
    {
        try { $service->analyze($searchTerm); return back()->with('success','Qwen suggestion created for '.$searchTerm->display_term.'.'); }
        catch (\Throwable $e) { return back()->withErrors(['ai'=>$e->getMessage()]); }
    }

    public function update(Request $request, SearchTerm $searchTerm, SearchTermResolver $resolver, DiscoveryCacheService $cache): RedirectResponse
    {
        $data=$request->validate([
            'status'=>['required','in:approved,rejected,pending'], 'canonical_term'=>['nullable','string','max:120'],
            'synonyms'=>['nullable','array','max:12'],'synonyms.*'=>['string','max:120'],'is_content_opportunity'=>['boolean'],
        ]);
        $mapping=SearchTermMapping::query()->firstOrCreate(['search_term_id'=>$searchTerm->id]);
        $mapping->update([
            'status'=>$data['status'],
            'approved_canonical_term'=>$data['status']==='approved' ? trim((string) ($data['canonical_term'] ?: $mapping->suggested_canonical_term ?: $searchTerm->display_term)) : null,
            'approved_synonyms'=>$data['status']==='approved' ? collect($data['synonyms'] ?? [])->map(fn ($v)=>trim($v))->filter()->unique()->values()->all() : null,
            'reviewed_by_user_id'=>$request->user()->id,'reviewed_at'=>now(),
        ]);
        $searchTerm->update(['is_content_opportunity'=>(bool)($data['is_content_opportunity'] ?? false)]);
        $resolver->flush($searchTerm->normalized_term); $cache->invalidate();
        return back()->with('success','Search-term intelligence updated.');
    }
}
