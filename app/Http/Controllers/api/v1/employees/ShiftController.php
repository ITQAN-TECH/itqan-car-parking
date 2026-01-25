<?php

namespace App\Http\Controllers\api\v1\employees;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function startShift(Request $request)
    {
        $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
        ]);
        $employee = Auth::guard('employees')->user();
        if (! $employee->locations->contains($request->location_id)) {
            return response()->json([
                'success' => false,
                'message' => __('responses.you are not authorized to start a shift in this location'),
            ], 400);
        }
        if ($employee->activeShift) {
            return response()->json([
                'success' => false,
                'message' => __('responses.shift already started'),
            ], 400);
        }
        $shift = $employee->shifts()->create([
            'location_id' => $request->location_id,
            'start_time' => now(),
            'status' => 'open',
        ]);
        return response()->json([
            'success' => true,
            'message' => __('responses.shift started successfully'),
            'shift' => $shift,
        ]);
    }

    public function endShift()
    {
        $employee = Auth::guard('employees')->user();
        if (! $employee->activeShift) {
            return response()->json([
                'success' => false,
                'message' => __('responses.shift not started'),
            ], 400);
        }
        
        $shift = $employee->activeShift;
        $shift->update([
            'end_time' => now(),
            'status' => 'closed',
        ]);
        
        // Get all tickets opened in this shift
        $tickets = $shift->tickets()->with(['car', 'invoice', 'location', 'employeeOpened', 'employeeClosed'])->get();
        
        // Calculate total revenues
        $totalRevenue = $tickets->sum(function ($ticket) {
            return $ticket->invoice?->price ?? 0;
        });
        
        $cashRevenue = $tickets->filter(function ($ticket) {
            return $ticket->invoice && $ticket->invoice->payment_method === 'cash';
        })->sum(function ($ticket) {
            return $ticket->invoice->price;
        });
        
        $onlineRevenue = $tickets->filter(function ($ticket) {
            return $ticket->invoice && $ticket->invoice->payment_method === 'online';
        })->sum(function ($ticket) {
            return $ticket->invoice->price;
        });
        
        // Calculate shift duration in hours and minutes
        // $durationInMinutes = $shift->start_time->diffInMinutes($shift->end_time);
        // $hours = floor($durationInMinutes / 60);
        // $minutes = $durationInMinutes % 60;
        // $shiftDuration = $hours . ' ' . __('responses.hours') . ' ' . $minutes . ' ' . __('responses.minutes');
        
        return response()->json([
            'success' => true,
            'message' => __('responses.shift ended successfully'),
            'shift' => $shift,
            'location_name' => $shift->location->name,
            // 'shift_duration' => $shiftDuration,
            'total_revenue' => $totalRevenue,
            'cash_revenue' => $cashRevenue,
            'online_revenue' => $onlineRevenue,
            'tickets_count' => $tickets->count(),
            // 'tickets' => $tickets,
        ]);
    }
}
