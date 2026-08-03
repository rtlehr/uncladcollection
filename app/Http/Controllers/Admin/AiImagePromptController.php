<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Services\Ai\ContentStudio\ImagePromptGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiImagePromptController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/AiContent/PromptGenerator/Index', [
            'recent' => AiGeneration::where('feature', 'image_prompt')
                ->latest()
                ->limit(10)
                ->get(['id', 'uuid', 'input_text', 'output_text', 'status', 'created_at', 'input_context']),
        ]);
    }

    public function store(Request $request, ImagePromptGenerator $generator): RedirectResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:5000',
            'intended_use' => 'required|in:general_image,blog_header,blog_inline,collection_cover,category_banner,advertisement,social_media,email_graphic,landing_page',
            'content_context' => 'required|in:general,adult_naturism,family_naturism',
            'output_mode' => 'required|in:content_only,content_composition,full',
            'body_detail_level' => 'required|in:contextual,natural_detail,detailed_adult_anatomy',
            'description_depth' => 'required|in:compact,standard,detailed,expanded',
            'character_detail_level' => 'required|in:minimal,standard,detailed,very_detailed',
            'environment_detail_level' => 'required|in:minimal,standard,detailed,rich',
            'describe_every_visible_person' => 'required|boolean',
            'orientation' => 'required|in:landscape,portrait,square,auto',
            'additional_instructions' => 'nullable|string|max:3000',
        ]);

        $generation = $generator->generate($validated, $request->user()?->id);

        return back()
            ->with('success', 'Image prompt generated.')
            ->with('generated_prompt', $generation->output_text);
    }

    public function history(): Response
    {
        return Inertia::render('Admin/AiContent/Generations/Index', [
            'items' => AiGeneration::with('requester:id,name')->latest()->paginate(40),
        ]);
    }
}
