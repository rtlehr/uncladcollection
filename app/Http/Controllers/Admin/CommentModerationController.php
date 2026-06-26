<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Services\AdminActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentModerationController extends Controller
{
    public function __construct(
        protected AdminActivityService $adminActivityService
    ) {
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $filter = $request->string('filter')->toString();

        $comments = Comment::query()
            ->with([
                'user:id,name,username,email,avatar_path',
                'commentable',
                'parent:id,body,user_id',
            ])
            ->withCount(['likes', 'reports'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('body', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($filter === 'reported', fn ($query) => $query->where('reports_count', '>', 0))
            ->when($filter === 'pinned', fn ($query) => $query->where('is_pinned', true))
            ->when($filter === 'pending', fn ($query) => $query->where('status', Comment::STATUS_PENDING))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Comments/Index', [
            'comments' => $comments,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'filter' => $filter,
            ],
            'statuses' => [
                Comment::STATUS_PENDING,
                Comment::STATUS_APPROVED,
                Comment::STATUS_HIDDEN,
                Comment::STATUS_SPAM,
                Comment::STATUS_DELETED,
            ],
        ]);
    }

    public function approve(Comment $comment): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_comments'), 403);

        $comment->update([
            'status' => Comment::STATUS_APPROVED,
            'approved_at' => now(),
            'hidden_at' => null,
        ]);

        $this->adminActivityService->log(
            action: 'approved',
            subject: $comment,
            description: 'Approved comment.'
        );

        return back()->with('success', 'Comment approved.');
    }

    public function hide(Comment $comment): RedirectResponse
    {
       abort_unless(auth()->user()?->can('manage_comments'), 403);

        $comment->update([
            'status' => Comment::STATUS_HIDDEN,
            'hidden_at' => now(),
        ]);

        $this->adminActivityService->log(
            action: 'hidden',
            subject: $comment,
            description: 'Hid comment.'
        );

        return back()->with('success', 'Comment hidden.');
    }

    public function restore(Comment $comment): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_comments'), 403);

        $comment->update([
            'status' => Comment::STATUS_APPROVED,
            'approved_at' => $comment->approved_at ?? now(),
            'hidden_at' => null,
        ]);

        $this->adminActivityService->log(
            action: 'restored',
            subject: $comment,
            description: 'Restored comment.'
        );

        return back()->with('success', 'Comment restored.');
    }

    public function pin(Comment $comment): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_comments'), 403);

        $comment->update([
            'is_pinned' => true,
        ]);

        $this->adminActivityService->log(
            action: 'pinned',
            subject: $comment,
            description: 'Pinned comment.'
        );

        return back()->with('success', 'Comment pinned.');
    }

    public function unpin(Comment $comment): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_comments'), 403);

        $comment->update([
            'is_pinned' => false,
        ]);

        $this->adminActivityService->log(
            action: 'unpinned',
            subject: $comment,
            description: 'Unpinned comment.'
        );

        return back()->with('success', 'Comment unpinned.');
    }

    public function spam(Comment $comment): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_comments'), 403);

        $comment->update([
            'status' => Comment::STATUS_SPAM,
            'hidden_at' => now(),
        ]);

        $this->adminActivityService->log(
            action: 'spam',
            subject: $comment,
            description: 'Marked comment as spam.'
        );

        return back()->with('success', 'Comment marked as spam.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
    
        abort_unless(auth()->user()?->can('manage_comments'), 403);

        $comment->update([
            'status' => Comment::STATUS_DELETED,
        ]);

        $this->adminActivityService->log(
            action: 'deleted',
            subject: $comment,
            description: 'Deleted comment.'
        );

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    public function reports(Request $request): Response
    {
        $status = $request->string('status')->toString();

        $reports = CommentReport::query()
            ->with([
                'user:id,name,username,email',
                'reviewer:id,name,username,email',
                'comment.user:id,name,username,email,avatar_path',
                'comment.commentable',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Comments/Reports', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
            ],
            'statuses' => [
                CommentReport::STATUS_PENDING,
                CommentReport::STATUS_REVIEWED,
                CommentReport::STATUS_DISMISSED,
            ],
        ]);
    }

    public function dismissReport(CommentReport $commentReport): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_comments'), 403);

        $commentReport->update([
            'status' => CommentReport::STATUS_DISMISSED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Report dismissed.');
    }

    public function markReportReviewed(CommentReport $commentReport): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage_comments'), 403);

        $commentReport->update([
            'status' => CommentReport::STATUS_REVIEWED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Report marked reviewed.');
    }
}