<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\CommentReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogCommentController extends Controller
{
    public function index(BlogPost $blogPost): JsonResponse
    {
        if (! $blogPost->comments_visible) {
            return response()->json([
                'comments' => [],
            ]);
        }

        $comments = $blogPost->approvedComments()
            ->root()
            ->with([
                'user:id,name,username,avatar_path',
                'approvedReplies.user:id,name,username,avatar_path',
                'approvedReplies.approvedReplies.user:id,name,username,avatar_path',
            ])
            ->withCount('likes')
            ->pinnedFirst()
            ->latest()
            ->paginate(10);

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function store(Request $request, BlogPost $blogPost): RedirectResponse
    {
        if (! $blogPost->comments_enabled) {
            return back()->with('error', 'Comments are closed for this article.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $parent = null;
        $depth = 0;

        if (! empty($validated['parent_id'])) {
            $parent = Comment::query()
                ->where('commentable_type', BlogPost::class)
                ->where('commentable_id', $blogPost->id)
                ->whereNull('deleted_at')
                ->findOrFail($validated['parent_id']);

            $depth = min(((int) $parent->depth) + 1, 2);
        }

        $blogPost->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $parent?->id,
            'body' => $validated['body'],
            'status' => $blogPost->comments_require_approval
                ? Comment::STATUS_PENDING
                : Comment::STATUS_APPROVED,
            'depth' => $depth,
            'approved_at' => $blogPost->comments_require_approval
                ? null
                : now(),
        ]);

        return back()->with(
            'success',
            $blogPost->comments_require_approval
                ? 'Comment submitted and awaiting approval.'
                : 'Comment posted.'
        );
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        abort_unless(
            $request->user()->id === $comment->user_id || $request->user()->can('manage_comments'),
            403
        );

        abort_if(
            $comment->status === Comment::STATUS_DELETED || $comment->trashed(),
            403,
            'This comment cannot be edited.'
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $comment->update([
            'body' => $validated['body'],
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        abort_unless(
            $request->user()->id === $comment->user_id || $request->user()->can('manage_comments'),
            403
        );

        $comment->update([
            'status' => Comment::STATUS_DELETED,
        ]);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    public function toggleLike(Request $request, Comment $comment): RedirectResponse
    {
        abort_if(
            $comment->status !== Comment::STATUS_APPROVED || $comment->trashed(),
            403
        );

        $existingLike = $comment->likes()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();

            if ($comment->likes_count > 0) {
                $comment->decrement('likes_count');
            }
        } else {
            $comment->likes()->create([
                'user_id' => $request->user()->id,
            ]);

            $comment->increment('likes_count');
        }

        return back();
    }

    public function report(Request $request, Comment $comment): RedirectResponse
    {
        abort_if(
            $comment->status !== Comment::STATUS_APPROVED || $comment->trashed(),
            403
        );

        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                Rule::in(['spam', 'harassment', 'abuse', 'off_topic', 'other']),
            ],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = CommentReport::query()->firstOrCreate(
            [
                'comment_id' => $comment->id,
                'user_id' => $request->user()->id,
            ],
            [
                'reason' => $validated['reason'],
                'details' => $validated['details'] ?? null,
                'status' => CommentReport::STATUS_PENDING,
            ]
        );

        if ($report->wasRecentlyCreated) {
            $comment->increment('reports_count');
        }

        return back()->with('success', 'Comment reported.');
    }
}