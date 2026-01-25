<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-employees')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|in:employee,supervisor',
            'status' => 'sometimes|nullable|boolean',
        ]);
        $employees = Employee::when($request->search, function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')
                ->orWhere('phone', 'like', '%' . $request->search . '%');
        })->when($request->type, function ($query) use ($request) {
            $query->where('type', $request->type);
        })->when($request->status, function ($query) use ($request) {
            $query->where('status', $request->status);
        })
        ->with('locations')
        ->latest()->paginate();

        $data = [];
        $data['totalCount'] = Employee::count();
        $data['employeeCount'] = Employee::where('type', 'employee')->count();
        $data['supervisorCount'] = Employee::where('type', 'supervisor')->count();

        return response()->json([
            'success' => true,
            'message' => __('responses.all employees'),
            'data' => $data,
            'employees' => $employees,
        ]);
    }

    public function show($employee_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-employees')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $employee = Employee::findOrFail($employee_id);

        return response()->json([
            'success' => true,
            'message' => __('responses.employee'),
            'employee' => $employee,
        ]);
    }

    public function store(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('create-employees')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email',
            'phone' => 'required|string|max:255|unique:employees,phone',
            'password' => 'required|string|max:255|min:8|confirmed',
            'type' => 'required|string|in:employee,supervisor',
        ]);
        try {
            DB::beginTransaction();
            $employee = Employee::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password,
                'type' => $request->type,
            ]);
            if ($request->hasFile('image') && $request->image != null) {
                $name = $request->image->hashName();
                $filename = time() . '_' . uniqid() . '_' . $name;
                $request->image->storeAs('public/media/', $filename);
                $employee->update([
                    'image' => $filename,
                ]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('responses.done'),
                'employee' => $employee,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function update(Request $request, $employee_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('edit-employees')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee_id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('employees', 'phone')->ignore($employee_id)],
            'password' => 'sometimes|nullable|string|max:255|min:8|confirmed',
            'password_confirmation' => 'sometimes|nullable|string|max:255|min:8',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:7168',
            'type' => 'sometimes|nullable|string|in:employee,supervisor',
        ]);
        $employee = Employee::findOrFail($employee_id);
        try {
            DB::beginTransaction();
            $employee->update([
                'name' => $request->name ?? $employee->name,
                'email' => $request->email ?? $employee->email,
                'phone' => $request->phone ?? $employee->phone,
                'password' => $request->password ?? $employee->password,
                'type' => $request->type ?? $employee->type,
            ]);
            if ($request->hasFile('image') && $request->image != null) {
                $name = $request->image->hashName();
                $filename = time() . '_' . uniqid() . '_' . $name;
                $request->image->storeAs('public/media/', $filename);
                $employee->update([
                    'image' => $filename,
                ]);
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('responses.done'),
                'employee' => $employee,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function changePassword(Request $request, $employee_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('edit-employees')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'old_password' => 'required|string|max:255|min:8',
            'password' => 'required|string|max:255|min:8|confirmed',
            'password_confirmation' => 'required|string|max:255|min:8',
        ]);
        $employee = Employee::findOrFail($employee_id);
        try {
            DB::beginTransaction();
            if (! Hash::check($request->old_password, $employee->password)) {
                return response()->json([
                    'success' => false,
                    'message' => __('responses.The password is incorrect'),
                ], 400);
            }
            $employee->update([
                'password' => $request->password,
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('responses.done'),
                'employee' => $employee,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function destroy($employee_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('delete-employees')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $employee = Employee::findOrFail($employee_id);
        try {
            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => __('responses.done'),
                'employee' => $employee,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('responses.you cannot delete employee'),
            ], 400);
        }
    }

    public function changeStatus(Request $request, $employee_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('edit-employees')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'status' => 'required|boolean',
        ]);
        $employee = Employee::findOrFail($employee_id);
        $employee->update([
            'status' => $request->status,
        ]);

        return response()->noContent();
    }

    public function assignLocation(Request $request, $employee_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('edit-employees')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'location_ids' => 'required|array',
            'location_ids.*' => 'required|exists:locations,id',
        ]);
        $locations = Location::whereIn('id', $request->location_ids)->get();
        if ($locations->count() != count($request->location_ids)) {
            return response()->json([
                'success' => false,
                'message' => __('responses.location not found'),
            ], 400);
        }
        $employee = Employee::findOrFail($employee_id);
        $employee->locations()->sync($request->location_ids);

        return response()->json([
            'success' => true,
            'message' => __('responses.done'),
            'employee' => $employee->load('locations'),
        ]);
    }
}
