<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-shifts')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'sometimes|nullable|string|max:255',
        ]);
        $shifts = Shift::when($request->search, function ($query) use ($request) {
            $query->where('start_time', 'like', '%' . $request->search . '%')
                ->orWhere('end_time', 'like', '%' . $request->search . '%')
                ->orWhereHas('employee', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('phone', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                })
                ->orWhereHas('location', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                });
        })
            ->with('employee', 'location')
            ->latest()
            ->paginate();

        $data = [];
        $data['totalEmployeesCount'] = Shift::where('status', 'open')->count();
        $data['totalLocationsCount'] = Shift::where('status', 'open')->with('location')->get()->unique('location_id')->count();
        // متوسط مدة المناوبات النشطة (بـ الساعات)
        $activeShifts = Shift::where('status', 'open')->get();
        if ($activeShifts->count() > 0) {
            $totalDurationMinutes = $activeShifts->sum(function ($shift) {
                $start = $shift->start_time ? \Carbon\Carbon::parse($shift->start_time) : null;
                $now = now();
                return $start ? $start->diffInMinutes($now) : 0;
            });
            $averageActiveShiftDurationHours = $totalDurationMinutes / 60 / $activeShifts->count();
        } else {
            $averageActiveShiftDurationHours = 0;
        }
        $data['averageActiveShiftDurationHours'] = round($averageActiveShiftDurationHours, 2);
        return response()->json([
            'success' => true,
            'message' => __('responses.all shifts'),
            'data' => $data,
            'shifts' => $shifts,
        ]);
    }

    public function show($shift_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-shifts')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $shift = Shift::with('employee', 'location')->findOrFail($shift_id);
        return response()->json([
            'success' => true,
            'message' => __('responses.shift'),
            'shift' => $shift,
        ]);
    }
}
