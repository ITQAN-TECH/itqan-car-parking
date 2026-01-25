<?php

namespace App\Http\Controllers\api\v1\employees\notifications\database;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatabaseNotificationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:read,unread',
            'sort_by' => 'nullable|in:latest,oldest',
        ]);

        $employee = Auth::guard('employees')->user();
        $query = $employee->notifications();

        // تطبيق فلاتر الحالة والترتيب
        if ($request->has('status')) {
            $status = strtolower($request->input('status'));
            if ($status === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($status === 'unread') {
                $query->whereNull('read_at');
            }
        }
        $sortBy = $request->input('sort_by', 'latest');
        ($sortBy === 'oldest') ? $query->oldest() : $query->latest();

        // جلب الإشعارات
        $notifications = $query->paginate();

        return response()->json([
            'success' => true,
            'message' => __('responses.all notifications for this user'),
            'notifications' => $notifications,
        ]);
    }

    public function show($id)
    {
        $employee = Auth::guard('employees')->user();
        $notification = $employee->notifications()->findOrFail($id);
        if ($notification->read_at == null) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'message' => __('responses.show notification'),
            'notification' => $notification,
        ]);
    }

    public function markAsRead($id)
    {
        $employee = Auth::guard('employees')->user();
        $notification = $employee->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => __('responses.notification mark as read successfully'),
        ], 200);
    }

    public function markAllAsRead()
    {
        $employee = Auth::guard('employees')->user();
        $employee->unreadNotifications->markAsRead();

        // إعادة جلب الإشعارات المرقّمة (Paginated) للإرجاع في الرد
        $notifications = $employee->notifications()->latest()->paginate();

        return response()->json([
            'success' => true,
            'message' => __('responses.all notifications mark as read successfully'),
            'notifications' => $notifications,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string',
        ]);

        $ids = $request->input('ids');
        $employee = Auth::guard('employees')->user();
        $deletedCount = $employee->notifications()
            ->whereIn('id', $ids)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => __('responses.notifications deleted successfully'),
            'deleted_count' => $deletedCount,
        ]);
    }

    public function toggleNotifications()
    {
        $employee = Auth::guard('employees')->user();
        $newStatus = ! $employee->receive_notifications;
        $employee->update(['receive_notifications' => $newStatus]);
        $message = $newStatus
            ? __('responses.Notifications enabled successfully')
            : __('responses.Notifications disabled successfully');

        return response()->json([
            'success' => true,
            'message' => $message,
            'receive_notifications' => $newStatus,
        ]);
    }
}
