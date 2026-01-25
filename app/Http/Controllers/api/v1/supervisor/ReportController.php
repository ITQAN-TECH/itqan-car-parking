<?php

namespace App\Http\Controllers\api\v1\supervisor;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
        ]);
        $start_date = Carbon::parse($request->start_date)->startOfDay() ?? Carbon::parse(now())->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay() ?? Carbon::parse(now())->endOfDay();

        $supervisor = Auth::guard('employees')->user();
        if (!$supervisor->activeShift) {
            return response()->json([
                'success' => false,
                'message' => __('responses.shift not started'),
            ], 400);
        }
        $location_id = $supervisor->activeShift->location_id;
        $tickets = Ticket::where('location_id', $location_id)->whereBetween('end_time', [$start_date, $end_date])
            ->has('invoice')->get();
        $total_revenue = $tickets->sum(function ($ticket) {
            return $ticket->invoice?->price ?? 0;
        });
        $cash_revenue = $tickets->filter(function ($ticket) {
            return $ticket->invoice && $ticket->invoice->payment_method === 'cash';
        })->sum(function ($ticket) {
            return $ticket->invoice->price;
        });
        $online_revenue = $tickets->filter(function ($ticket) {
            return $ticket->invoice && $ticket->invoice->payment_method === 'online';
        })->sum(function ($ticket) {
            return $ticket->invoice->price;
        });
        $ticket_count = $tickets->count();
        return response()->json([
            'success' => true,
            'message' => __('responses.report fetched successfully'),
            'total_revenue' => $total_revenue,
            'cash_revenue' => $cash_revenue,
            'online_revenue' => $online_revenue,
            'ticket_count' => $ticket_count,
            'start_date' => Carbon::parse($start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($end_date)->format('Y-m-d'),
        ]);
    }
}
