<?php

namespace App\Http\Controllers\api\v1\guests;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Ticket;
use App\Jobs\SendNotificationJob;
use App\Notifications\TicketRequestedNotification;

class TicketController extends Controller
{
    public function show($ticket_number)
    {
        $ticket = Ticket::where('ticket_number', $ticket_number)->with('car', 'location')->firstOrFail();
        return response()->json([
            'success' => true,
            'message' => __('responses.ticket fetched successfully'),
            'ticket' => $ticket,
        ]);
    }

    public function request($ticket_number)
    {
        $ticket = Ticket::where('ticket_number', $ticket_number)->firstOrFail();
        if ($ticket->type != 'park_car') {
            return response()->json([
                'success' => false,
                'message' => __('responses.only park car tickets can be requested'),
            ], 400);
        }
        if ($ticket->is_requested) {
            return response()->json([
                'success' => false,
                'message' => __('responses.ticket already requested'),
            ], 400);
        }
        if ($ticket->status == 'completed') {
            return response()->json([
                'success' => false,
                'message' => __('responses.ticket already completed'),
            ], 400);
        }
        if ($ticket->status == 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => __('responses.ticket already cancelled'),
            ], 400);
        }
        if ($ticket->start_time > now()) {
            return response()->json([
                'success' => false,
                'message' => __('responses.ticket not started yet'),
            ], 400);
        }
        $ticket->update(['is_requested' => true, 'requested_time' => now()]);
        $employees = Employee::whereHas('activeShift', function ($query) use ($ticket) {
            $query->where('location_id', $ticket->location_id);
        })->get();
        $notification = new TicketRequestedNotification($ticket);
        $fcmTitle = __('responses.Client requested a ticket');
        $fcmBody = __('responses.Client requested a ticket number') . $ticket->ticket_number;
        dispatch(new SendNotificationJob(collect($employees), $notification, $fcmTitle, $fcmBody));
        return response()->json([
            'success' => true,
            'message' => __('responses.ticket requested successfully'),
            'ticket' => $ticket->load('car'),
        ]);
    }
}
