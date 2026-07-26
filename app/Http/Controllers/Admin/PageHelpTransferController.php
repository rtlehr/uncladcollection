<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageHelp;
use App\Services\PageHelp\PageHelpTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class PageHelpTransferController extends Controller
{
    public function export(PageHelpTransferService $transfer): Response
    {
        $this->authorize('viewAny', PageHelp::class);

        $filename = 'page-help-'.now()->format('Y-m-d-His').'.json';

        return response($transfer->exportJson(), 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function import(Request $request, PageHelpTransferService $transfer): RedirectResponse
    {
        $this->authorize('create', PageHelp::class);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:json,txt', 'max:5120'],
            'mode' => ['required', 'in:merge,replace'],
            'confirm_replace' => ['nullable', 'accepted_if:mode,replace'],
        ]);

        try {
            $summary = $transfer->importJson(
                file_get_contents($validated['file']->getRealPath()),
                $validated['mode'],
                $request->user(),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        $message = sprintf(
            'Page Help import complete: %d created, %d updated, %d unchanged, %d removed.',
            $summary['created'],
            $summary['updated'],
            $summary['unchanged'],
            $summary['deleted'],
        );

        if ($summary['missing_roles'] !== [] || $summary['missing_permissions'] !== []) {
            $message .= ' Some role or permission targets were not found; review the import warnings.';
        }

        return back()
            ->with('success', $message)
            ->with('page_help_import_summary', $summary);
    }
}
