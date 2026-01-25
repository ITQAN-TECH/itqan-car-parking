<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-invoices')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'sometimes|nullable|string|max:255',
        ]);
        $invoices = Invoice::when($request->search, function ($query) use ($request) {
            $query->whereHas('ticket', function ($query) use ($request) {
                $query->whereHas('car', function ($query) use ($request) {
                    $query->where('owner_name', 'like', '%' . $request->search . '%')
                        ->orWhere('car_name', 'like', '%' . $request->search . '%')
                        ->orWhere('owner_phone', 'like', '%' . $request->search . '%')
                        ->orWhere('car_number', 'like', '%' . $request->search . '%')
                        ->orWhere('car_letter', 'like', '%' . $request->search . '%');
                })->orWhereHas('location', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                })
                    ->orWhere('ticket_number', 'like', '%' . $request->search . '%');
            })->orWhere('price', 'like', '%' . $request->search . '%')
                ->orWhere('payment_method', 'like', '%' . $request->search . '%');
        })
            ->with('ticket', 'ticket.car', 'ticket.location')
            ->latest()
            ->paginate();

        $data = [];
        $data['all_invoices']['total_count'] = Invoice::count();
        $data['all_invoices']['total_amount'] = round(Invoice::sum('price'), 2);
        $data['cash_invoices']['total_count'] = Invoice::where('payment_method', 'cash')->count();
        $data['cash_invoices']['total_amount'] = round(Invoice::where('payment_method', 'cash')->sum('price'), 2);
        $data['online_invoices']['total_count'] = Invoice::where('payment_method', 'online')->count();
        $data['online_invoices']['total_amount'] = round(Invoice::where('payment_method', 'online')->sum('price'), 2);
        $data['free_invoices']['total_count'] = Invoice::where('payment_method', 'free')->count();
        $data['free_invoices']['total_amount'] = round(Invoice::where('payment_method', 'free')->sum('price'), 2);
        return response()->json([
            'success' => true,
            'message' => __('responses.all invoices'),
            'data' => $data,
            'invoices' => $invoices,
        ]);
    }

    public function show($invoice_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-invoices')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $invoice = Invoice::with('ticket', 'ticket.car', 'ticket.location')->findOrFail($invoice_id);
        return response()->json([
            'success' => true,
            'message' => __('responses.invoice'),
            'invoice' => $invoice,
        ]);
    }
}
