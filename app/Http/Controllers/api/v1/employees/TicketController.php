<?php

namespace App\Http\Controllers\api\v1\employees;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Location;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use App\services\JawalySMSService;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $employee = Auth::guard('employees')->user();
        $location_id = $employee->activeShift?->location_id ?? null;
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:open,closed',
        ]);
        $tickets = Ticket::when($location_id, function ($query) use ($location_id) {
            $query->where('location_id', $location_id);
        })
            ->when($request->status == 'open', function ($query) use ($request) {
                $query->where('status', 'in_progress');
            })
            ->when($request->status == 'closed', function ($query) use ($request) {
                $query->where(function ($query) {
                    $query->where('status', 'completed')
                        ->orWhere('status', 'cancelled');
                })->where('created_at', '>=', now()->subDay());
            })
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->whereHas('car', function ($carQuery) use ($request) {
                        $carQuery->where('owner_name', 'like', '%' . $request->search . '%')
                            ->orWhere('owner_phone', 'like', '%' . $request->search . '%')
                            ->orWhere('car_number', 'like', '%' . $request->search . '%')
                            ->orWhere('car_letter', 'like', '%' . $request->search . '%');
                    })->orWhere('ticket_number', 'like', '%' . $request->search . '%');
                });
            })
            ->with('car', 'invoice', 'location', 'employeeOpened', 'employeeClosed')
            ->latest()->paginate();

        // حساب السعر لكل تذكرة
        $tickets->getCollection()->transform(function ($ticket) {
            $price = 0;
            if ($ticket->status == 'in_progress') {
                if ($ticket->location->type == 'park_car') {
                    $price = $ticket->location->price;
                } elseif ($ticket->location->type == 'self_parking') {
                    $hours = $ticket->start_time->diffInHours(now());
                    if ($hours < 1) {
                        $hours = 1;
                    }
                    $price = round($ticket->location->price * $hours, 2);
                }

                // إذا كان لدى السيارة اشتراك نشط، السعر يكون صفر
                if ($ticket->car->activeSubscription) {
                    $price = 0;
                }

                // إضافة السعر المحسوب للتذكرة
                $ticket->calculated_price = $price;
            }
            return $ticket;
        });

        return response()->json([
            'success' => true,
            'message' => __('responses.all tickets'),
            'tickets' => $tickets,
        ]);
    }

    public function show($ticket_id)
    {
        $ticket = Ticket::findOrFail($ticket_id);
        if($ticket->status == 'in_progress') {
            $price = 0;
            if ($ticket->location->type == 'park_car') {
                $price = $ticket->location->price;
            } elseif ($ticket->location->type == 'self_parking') {
                $hours = $ticket->start_time->diffInHours(now());
                if ($hours < 1) {
                    $hours = 1;
                }
                $price = round($ticket->location->price * $hours, 2);
            }
            if ($ticket->car->activeSubscription) {
                $price = 0;
            }
            $ticket->calculated_price = $price;
        }
        return response()->json([
            'success' => true,
            'message' => __('responses.ticket'),
            'ticket' => $ticket->load('car', 'invoice', 'location', 'employeeOpened', 'employeeClosed'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'car_id' => 'required|exists:cars,id',
        ]);
        $employee = Auth::guard('employees')->user();
        if (!$employee->activeShift) {
            return response()->json([
                'success' => false,
                'message' => __('responses.shift not started'),
            ], 400);
        }
        $car = Car::findOrFail($request->car_id);
        try {
            DB::beginTransaction();
            $location = $employee->activeShift?->location ?? null;
            if (! $employee->locations->contains($location->id)) {
                return response()->json([
                    'success' => false,
                    'message' => __('responses.you are not authorized to create a ticket in this location'),
                ], 400);
            }
            $ticket = Ticket::create([
                'shift_id' => $employee->activeShift->id,
                'car_id' => $car->id,
                'location_id' => $location->id,
                'employee_opened_id' => $employee->id,
                'type' => $location->type,
                'status' => 'in_progress',
                'start_time' => now(),
                'is_requested' => false,
                'requested_time' => null,
            ]);

            // إعداد الرسالة حسب نوع التذكرة
            $message = '';

            if ($ticket->type == 'self_parking') {
                $message = "شكراً لثقتك\n" .
                    "تم فتح تذكرة جديدة لسيارتك {$car->car_number} {$car->car_letter}\n" .
                    "رقم التذكرة: {$ticket->ticket_number}\n" .
                    "الموقع: {$location->name}\n\n" .
                    "Thank you for your trust\n" .
                    "A new ticket has been opened for your car {$car->car_number} {$car->car_letter}\n" .
                    "Ticket number: {$ticket->ticket_number}\n" .
                    "Location: {$location->name}";
                // JawalySMSService::sendMessage($car->owner_phone, $message);
            } elseif ($ticket->type == 'park_car') {
                $apiLink = 'https://dashboardvalet.iw.net.sa/guests/tickets/' . $ticket->ticket_number;
                $message = "شكراً لثقتك\n" .
                    "تم فتح تذكرة جديدة لسيارتك {$car->car_number} {$car->car_letter}\n" .
                    "رقم التذكرة: {$ticket->ticket_number}\n" .
                    "الموقع: {$location->name}\n" .
                    "لطلب السيارة يرجى الضغط على الرابط أدناه\n" .
                    $apiLink . "\n\n" .
                    "Thank you for your trust\n" .
                    "A new ticket has been opened for your car {$car->car_number} {$car->car_letter}\n" .
                    "Ticket number: {$ticket->ticket_number}\n" .
                    "Location: {$location->name}\n" .
                    "To request your car, please click the link below\n" .
                    $apiLink;


                // JawalySMSService::sendMessage($car->owner_phone, $message);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('responses.ticket created successfully'),
                'ticket' => $ticket->load('car'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function cancel($ticket_id)
    {
        $ticket = Ticket::findOrFail($ticket_id);
        if ($ticket->status != 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => __('responses.ticket is not in progress'),
            ], 400);
        }
        $employee = Auth::guard('employees')->user();
        $ticket->update([
            'status' => 'cancelled',
            'end_time' => now(),
            'employee_closed_id' => $employee->id,
        ]);
        return response()->json([
            'success' => true,
            'message' => __('responses.ticket cancelled successfully'),
            'ticket' => $ticket->load('car'),
        ]);
    }

    public function close(Request $request, $ticket_id)
    {
        $request->validate([
            'payment_method' => 'required|string|in:cash,online,free',
        ]);
        $employee = Auth::guard('employees')->user();
        $ticket = Ticket::findOrFail($ticket_id);
        $car = $ticket->car;
        try {
            DB::beginTransaction();
            if ($ticket->status != 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => __('responses.ticket is not in progress'),
                ], 400);
            }
            if ($ticket->status == 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => __('responses.ticket is cancelled'),
                ], 400);
            }
            if (!$employee->activeShift) {
                return response()->json([
                    'success' => false,
                    'message' => __('responses.shift not started'),
                ], 400);
            }
            if ($employee->activeShift?->location_id != $ticket->location_id) {
                return response()->json([
                    'success' => false,
                    'message' => __('responses.you are not authorized to close this ticket'),
                ], 400);
            }
            $price = 0;
            if ($ticket->location->type == 'park_car') {
                $price = $ticket->location->price;
            } elseif ($ticket->location->type == 'self_parking') {
                $hours = $ticket->start_time->diffInHours(now());
                if ($hours < 1) {
                    $hours = 1;
                }
                $price = round($ticket->location->price * $hours, 2);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('responses.invalid location type'),
                ], 400);
            }
            if ($request->payment_method == 'free') {
                $price = 0;
            }
            if ($car->activeSubscription) {
                $price = 0;
            }
            $ticket->update([
                'status' => 'completed',
                'end_time' => now(),
                'employee_closed_id' => $employee->id,
            ]);
            $invoice = Invoice::create([
                'ticket_id' => $ticket->id,
                'price' => $price,
                'payment_method' => $price == 0 ? 'free' : $request->payment_method,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('responses.ticket closed successfully'),
                'ticket' => $ticket->load('car', 'invoice', 'location', 'employeeOpened', 'employeeClosed'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }
}
