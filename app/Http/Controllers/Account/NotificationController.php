<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();
        $query = $request->user()->notifications()->latest();
        if ($filter === 'unread') $query->whereNull('read_at');

        return Inertia::render('Account/Notifications/Index', [
            'notifications' => $query->paginate(20)->withQueryString()->through(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'category' => $notification->data['category'] ?? 'account',
                'title' => $notification->data['title'] ?? 'Account update',
                'message' => $notification->data['message'] ?? '',
                'action_url' => $notification->data['action_url'] ?? null,
                'action_label' => $notification->data['action_label'] ?? null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->diffForHumans(),
            ]),
            'filter' => $filter,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === (int) $request->user()->id && $notification->notifiable_type === $request->user()::class, 404);
        $notification->markAsRead();
        $url = $notification->data['action_url'] ?? null;
        return $url ? redirect()->to($url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }
}
