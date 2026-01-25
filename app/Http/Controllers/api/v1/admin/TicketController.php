<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Subscription;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-tickets')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|in:park_car,self_parking',
            'status' => 'sometimes|nullable|string|in:in_progress,completed,cancelled',
        ]);
        $tickets = Ticket::when($request->search, function ($query) use ($request) {
            $query->whereHas('car', function ($query) use ($request) {
                $query->where('owner_name', 'like', '%' . $request->search . '%')
                    ->orWhere('car_name', 'like', '%' . $request->search . '%')
                    ->orWhere('owner_phone', 'like', '%' . $request->search . '%')
                    ->orWhere('car_number', 'like', '%' . $request->search . '%')
                    ->orWhere('car_letter', 'like', '%' . $request->search . '%');
            })->orWhereHas('location', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })->orWhere('ticket_number', 'like', '%' . $request->search . '%')
                ->orWhere('start_time', 'like', '%' . $request->search . '%')
                ->orWhere('end_time', 'like', '%' . $request->search . '%');
        })->when($request->type, function ($query) use ($request) {
            $query->where('type', $request->type);
        })->when($request->status, function ($query) use ($request) {
            $query->where('status', $request->status);
        })
            ->with('car', 'location', 'employeeOpened', 'employeeClosed')
            ->latest()
            ->paginate();

        $data = [];
        $data['totalCount'] = Ticket::count();
        $data['openTicketsCount'] = Ticket::where('status', 'in_progress')->count();
        $data['closedTicketsCount'] = Ticket::where('status', 'completed')->count();
        $data['cancelledTicketsCount'] = Ticket::where('status', 'cancelled')->count();

        return response()->json([
            'success' => true,
            'message' => __('responses.all tickets'),
            'data' => $data,
            'tickets' => $tickets,
        ]);
    }

    public function show($ticket_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-tickets')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $ticket = Ticket::with('car', 'location', 'employeeOpened', 'employeeClosed')->findOrFail($ticket_id);
        return response()->json([
            'success' => true,
            'message' => __('responses.ticket'),
            'ticket' => $ticket,
        ]);
    }
}
