<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\BlogContentPerformanceService;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlogContentPerformanceReportController extends Controller
{
    public function index(Request $request, BlogContentPerformanceService $reports): Response
    {
        $filters = $this->validated($request);
        $period = AnalyticsPeriod::fromRequest($request);

        return Inertia::render('Admin/Analytics/Blog/Index', [
            'filters' => array_merge(['period' => $request->input('period', '30_days'), 'search' => '', 'author_id' => null, 'category_id' => null], $period->toArray(), $filters),
            'report' => $reports->report($period, $filters),
            'authors' => User::query()->whereHas('blogPosts')->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->where('category_type', 'blog')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, BlogPost $blogPost, BlogContentPerformanceService $reports): Response
    {
        $this->validated($request, false);
        $period = AnalyticsPeriod::fromRequest($request);
        $blogPost->load(['author:id,name', 'categories:id,name', 'tags:id,name']);

        return Inertia::render('Admin/Analytics/Blog/Show', [
            'filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()),
            'report' => $reports->detail($blogPost, $period),
        ]);
    }

    public function export(Request $request, BlogContentPerformanceService $reports): StreamedResponse
    {
        $filters = $this->validated($request);
        $period = AnalyticsPeriod::fromRequest($request);
        $filename = 'blog-content-performance-'.$period->start->toDateString().'-'.$period->end->toDateString().'.csv';

        return response()->streamDownload(function () use ($reports, $period, $filters): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Post ID', 'Title', 'Author', 'Categories', 'Views', 'Unique readers', 'Comments', 'Marketplace actions', 'Influenced buyers', 'Influenced orders', 'Influenced revenue cents', 'Engagement rate %']);
            foreach ($reports->exportRows($period, $filters) as $row) fputcsv($output, $row);
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validated(Request $request, bool $includeFilters = true): array
    {
        $rules = [
            'period' => ['nullable', 'in:7_days,30_days,90_days,year_to_date,custom'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:start_date'],
        ];
        if ($includeFilters) $rules += ['search' => ['nullable', 'string', 'max:120'], 'author_id' => ['nullable', 'integer', 'exists:users,id'], 'category_id' => ['nullable', 'integer', 'exists:categories,id']];
        return $request->validate($rules);
    }
}
