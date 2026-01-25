<?php

namespace App\Http\Controllers\api\v1\employees;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Store;
use App\services\JawalySMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);
        $employee = Employee::where('phone', $request->phone)->first();
        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => __('responses.credentials are incorrect'),
            ], 400);
        }
        if (! Hash::check($request->password, $employee->password)) {
            return response()->json([
                'success' => false,
                'message' => __('responses.credentials are incorrect'),
            ], 400);
        }
        if (! $employee->status) {
            return response()->json([
                'success' => false,
                'message' => __('responses.This employee is banned'),
            ], 400);
        }

        $token = $employee->createToken('employee_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('responses.login successfully'),
            'employee' => $employee,
            'token' => $token,
        ]);
    }

    public function logout()
    {
        $employee = Auth::guard('employees')->user();
        if ($employee->activeShift) {
            $employee->activeShift->update([
                'end_time' => now(),
                'status' => 'closed',
            ]);
        }
        $employee->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => __('responses.logout successfully'),
        ]);
    }
}
