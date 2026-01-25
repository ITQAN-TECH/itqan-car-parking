<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-locations')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|in:park_car,self_parking',
        ]);
        $locations = Location::when($request->search, function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('type', 'like', '%' . $request->search . '%');
        })->when($request->type, function ($query) use ($request) {
            $query->where('type', $request->type);
        })->latest()->paginate();
        
        $data=[];
        $data['totalCount'] = Location::count();
        $data['parkCarCount'] = Location::where('type', 'park_car')->count();
        $data['selfParkingCount'] = Location::where('type', 'self_parking')->count();

        return response()->json([
            'success' => true,
            'message' => __('responses.all locations'),
            'data' => $data,
            'locations' => $locations,
        ]);
    }

    public function search(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-locations')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);
        $locations = Location::when($request->search, function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('type', 'like', '%' . $request->search . '%');
        })->limit(10)->get();

        return response()->json([
            'success' => true,
            'message' => __('responses.all locations'),
            'locations' => $locations,
        ]);
    }

    public function show($location_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-locations')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $location = Location::findOrFail($location_id);

        return response()->json([
            'success' => true,
            'message' => __('responses.location'),
            'location' => $location,
        ]);
    }

    public function store(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('create-locations')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:park_car,self_parking',
            'price' => 'required|numeric|min:0',
            'duration_of_receiving_the_car' => 'required|integer|min:1',
        ]);
        try {
            DB::beginTransaction();
            $location = Location::create([
                'name' => $request->name,
                'type' => $request->type,
                'price' => $request->price,
                'duration_of_receiving_the_car' => $request->duration_of_receiving_the_car,
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('responses.done'),
                'location' => $location,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function update(Request $request, $location_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('edit-locations')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|in:park_car,self_parking',
            'price' => 'sometimes|nullable|numeric|min:0',
            'duration_of_receiving_the_car' => 'sometimes|nullable|integer|min:0',
        ]);
        $location = Location::findOrFail($location_id);
        try {
            DB::beginTransaction();
            $location->update([
                'name' => $request->name ?? $location->name,
                'type' => $request->type ?? $location->type,
                'price' => $request->price ?? $location->price,
                'duration_of_receiving_the_car' => $request->duration_of_receiving_the_car ?? $location->duration_of_receiving_the_car,
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('responses.done'),
                'location' => $location,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }


    public function destroy($location_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('delete-locations')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $location = Location::findOrFail($location_id);
        try {
            $location->delete();

            return response()->json([
                'success' => true,
                'message' => __('responses.done'),
                'location' => $location,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('responses.you cannot delete location'),
            ], 400);
        }
    }
}
