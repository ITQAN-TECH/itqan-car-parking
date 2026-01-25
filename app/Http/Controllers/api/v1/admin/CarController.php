<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-cars')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'sometimes|nullable|string|max:255',
        ]);
        $cars = Car::when($request->search, function ($query) use ($request) {
            $query->where('owner_name', 'like', '%' . $request->search . '%')
                ->orWhere('owner_phone', 'like', '%' . $request->search . '%')
                ->orWhere('car_name', 'like', '%' . $request->search . '%')
                ->orWhere('car_number', 'like', '%' . $request->search . '%')
                ->orWhere('car_letter', 'like', '%' . $request->search . '%');
        })
            ->latest()
            ->paginate();

        $data = [];
        $data['totalCount'] = Car::count();

        return response()->json([
            'success' => true,
            'message' => __('responses.all cars'),
            'data' => $data,
            'cars' => $cars,
        ]);
    }

    public function search(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-cars')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);
        $cars = Car::when($request->search, function ($query) use ($request) {
            $query->where('owner_name', 'like', '%' . $request->search . '%')
                ->orWhere('car_name', 'like', '%' . $request->search . '%')
                ->orWhere('owner_phone', 'like', '%' . $request->search . '%')
                ->orWhere('car_number', 'like', '%' . $request->search . '%')
                ->orWhere('car_letter', 'like', '%' . $request->search . '%');
        })
            ->limit(10)
            ->get();
        return response()->json([
            'success' => true,
            'message' => __('responses.all cars'),
            'cars' => $cars,
        ]);
    }
}
