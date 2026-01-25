<?php

namespace App\Http\Controllers\api\v1\employees\FCMToken;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FCMTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:255',
            'device_id' => 'nullable|string|max:255',
        ]);
        try {
            $employee = Auth::guard('employees')->user();

            // التحقق من وجود الرمز مسبقاً
            $existingToken = FcmToken::where('tokenable_id', $employee->id)
                ->where('tokenable_type', get_class($employee))
                ->where('token', $request->token)
                ->first();

            if (! $existingToken) {
                // إضافة الرمز الجديد
                $employee->fcmTokens()->create([
                    'token' => $request->token,
                    'device_id' => $request->device_id,
                ]);
            }

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:255',
        ]);
        try {
            $employee = Auth::guard('employees')->user();

            // حذف الرمز المحدد
            FcmToken::where('tokenable_id', $employee->id)
                ->where('tokenable_type', get_class($employee))
                ->where('token', $request->token)
                ->delete();

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }
}
