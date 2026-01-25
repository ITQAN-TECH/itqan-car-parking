<?php

namespace App\Http\Controllers\api\v1\employees;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CarController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);
        $cars = Car::when($request->search, function ($query) use ($request) {
            $query->where('owner_name', 'like', '%' . $request->search . '%')
                ->orWhere('owner_phone', 'like', '%' . $request->search . '%')
                ->orWhere('car_number', 'like', '%' . $request->search . '%')
                ->orWhere('car_name', 'like', '%' . $request->search . '%')
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

    public function show($car_id)
    {
        $car = Car::findOrFail($car_id);
        return response()->json([
            'success' => true,
            'message' => __('responses.car'),
            'car' => $car,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'owner_name' => 'nullable|string|max:255',
            'owner_phone' => 'required|string|max:255',
            'car_name' => 'nullable|string|max:255',
            'car_number' => 'required|string|max:255',
            'car_letter' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:7168',
        ]);
        if(Car::where('car_number', $request->car_number)->where('car_letter', $request->car_letter)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('responses.the car number and letter already exists.'),
            ], 400);
        }
        $car = Car::create([
            'owner_name' => $request->owner_name,
            'owner_phone' => $request->owner_phone,
            'car_name' => $request->car_name,
            'car_number' => $request->car_number,
            'car_letter' => $request->car_letter,
            'car_description' => $request->description,
        ]);
        if ($request->hasFile('image')) {
            $name = $request->image->hashName();
            $filename = time() . '_' . uniqid() . '_' . $name;
            $request->image->storeAs('public/media/', $filename);
            $car->update([
                'car_image' => $filename,
            ]);
        }
        $car->refresh();
        return response()->json([
            'success' => true,
            'message' => __('responses.car created successfully'),
            'car' => $car,
        ]);
    }

    public function update(Request $request, $car_id)
    {
        $request->validate([
            'owner_name' => 'sometimes|nullable|string|max:255',
            'owner_phone' => 'sometimes|nullable|string|max:255',
            'car_name' => 'sometimes|nullable|string|max:255',
            'car_number' => 'sometimes|nullable|string|max:255',
            'car_letter' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:255',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:7168',
        ]);
        $car = Car::findOrFail($car_id);
        if(Car::where('car_number', $request->car_number)->where('car_letter', $request->car_letter)->where('id', '!=', $car_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('responses.the car number and letter already exists.'),
            ], 400);
        }
        $car->update([
            'owner_name' => $request->owner_name ?? $car->owner_name,
            'owner_phone' => $request->owner_phone ?? $car->owner_phone,
            'car_name' => $request->car_name ?? $car->car_name,
            'car_number' => $request->car_number ?? $car->car_number,
            'car_letter' => $request->car_letter ?? $car->car_letter,
            'car_description' => $request->description ?? $car->car_description,
        ]);
        if ($request->hasFile('image')) {
            if($car->car_image) {
                Storage::delete('public/media/'.$car->car_image);
            }
            $name = $request->image->hashName();
            $filename = time() . '_' . uniqid() . '_' . $name;
            $request->image->storeAs('public/media/', $filename);
            $car->update([
                'car_image' => $filename,
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => __('responses.car updated successfully'),
            'car' => $car,
        ]);
    }
}
