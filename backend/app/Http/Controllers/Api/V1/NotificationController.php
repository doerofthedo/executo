<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $notifications = $user->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(static fn ($n) => [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'unknown',
                'data' => $n->data,
                'read_at' => $n->read_at?->toAtomString(),
                'created_at' => $n->created_at->toAtomString(),
            ]);

        return response()->json([
            'data' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification === null) {
            abort(404);
        }

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
