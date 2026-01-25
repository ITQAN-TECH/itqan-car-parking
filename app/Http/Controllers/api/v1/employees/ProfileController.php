<?php

namespace App\Http\Controllers\api\v1\employees;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function editProfile(Request $request)
    {
        $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore(Auth::guard('employees')->id())],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:7168'],
        ], [
            'email.unique' => __('responses.email already exists'),
        ]);
        $employee = Auth::guard('employees')->user();
        try {
            DB::beginTransaction();
            $employee->update([
                'name' => $request->name ?? $employee->name,
                'email' => $request->email ?? $employee->email,
            ]);
            if ($request->hasFile('image')) {
                if ($employee->image) {
                    Storage::delete('public/media/'.$employee->image);
                }
                $name = $request->image->hashName();
                $filename = time().'_'.uniqid().'_'.$name;
                $request->image->storeAs('public/media/', $filename);
                $employee->update([
                    'image' => $filename,
                ]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('responses.profile updated'),
                'employee' => $employee->refresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function show()
    {
        $employee = Auth::guard('employees')->user();
        
        // Get active shift location
        $activeShift = $employee->activeShift;
        $openTicketsCount = 0;
        
        if ($activeShift && $activeShift->location_id) {
            // Count open tickets (tickets with start_time but no end_time) in this location
            $openTicketsCount = Ticket::where('location_id', $activeShift->location_id)
                ->where('status', 'in_progress')
                ->count();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'employee',
            'employee' => $employee,
            'open_tickets_count' => $openTicketsCount,
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => ['required', 'string', 'min:8'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password', 'string', 'min:8'],
        ]);
        $employee = Auth::guard('employees')->user();
        if (! Hash::check($request->old_password, $employee->password)) {
            return response()->json([
                'success' => false,
                'message' => __('responses.The password is incorrect'),
            ], 400);
        }
        try {
            DB::beginTransaction();
            $employee->update([
                'password' => Hash::make($request->password),
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('responses.password changed successfully'),
                'employee' => $employee->refresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function getEmployeeLocations()
    {
        $employee = Auth::guard('employees')->user();
        $locations = $employee->locations;
        return response()->json([
            'success' => true,
            'message' => __('responses.employee locations'),
            'locations' => $locations,
        ]);
    }
}
