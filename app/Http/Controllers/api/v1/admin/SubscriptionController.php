<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-subscriptions')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'search' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|nullable|string|in:car,location',
            'is_active' => 'sometimes|nullable|boolean',
        ]);
        $subscriptions = Subscription::when($request->type, function ($query) use ($request) {
            $query->where('type', $request->type);
        })->when($request->has('is_active') && $request->is_active == 1, function ($query) use ($request) {
            $query->where('is_active', true);
        })->when($request->has('is_active') && $request->is_active == 0, function ($query) use ($request) {
            $query->where('is_active', false);
        })
        ->when($request->search, function ($query) use ($request) {
            $query->where(function ($q) use ($request) {
                $q->where(function ($subQuery) use ($request) {
                    $subQuery->whereHas('car', function ($carQuery) use ($request) {
                        $carQuery->where('owner_name', 'like', '%' . $request->search . '%')
                            ->orWhere('car_name', 'like', '%' . $request->search . '%')
                            ->orWhere('owner_phone', 'like', '%' . $request->search . '%')
                            ->orWhere('car_number', 'like', '%' . $request->search . '%')
                            ->orWhere('car_letter', 'like', '%' . $request->search . '%');
                    });
                })->orWhere(function ($subQuery) use ($request) {
                    $subQuery->whereHas('location', function ($locationQuery) use ($request) {
                        $locationQuery->where('name', 'like', '%' . $request->search . '%');
                    });
                })->orWhere('price', 'like', '%' . $request->search . '%')
                    ->orWhere('start_date', 'like', '%' . $request->search . '%')
                    ->orWhere('end_date', 'like', '%' . $request->search . '%');
            });
        })
            ->with('car', 'location')
            ->latest()
            ->paginate();

        $data = [];
        $data['totalCount'] = Subscription::count();
        $data['carSubscriptionCount'] = Subscription::where('type', 'car')->count();
        $data['locationSubscriptionCount'] = Subscription::where('type', 'location')->count();

        return response()->json([
            'success' => true,
            'message' => __('responses.all subscriptions'),
            'data' => $data,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function show($subscription_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-subscriptions')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $subscription = Subscription::with('car', 'location')->findOrFail($subscription_id);
        return response()->json([
            'success' => true,
            'message' => __('responses.subscription'),
            'subscription' => $subscription,
        ]);
    }

    public function storeCarSubscription(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('create-subscriptions')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'type' => ['required', 'string', Rule::in(['new', 'exist'])],
            'car_id' => [Rule::prohibitedIf($request->type == 'new'), Rule::requiredIf($request->type == 'exist'), 'exists:cars,id'],
            'car_name' => ['nullable', 'string', 'max:255', Rule::prohibitedIf($request->type == 'exist')],
            'car_number' => [Rule::requiredIf($request->type == 'new'), Rule::prohibitedIf($request->type == 'exist'), 'nullable', 'string', 'max:255'],
            'car_letter' => [Rule::prohibitedIf($request->type == 'exist'), Rule::requiredIf($request->type == 'new'), 'nullable', 'string', 'max:255'],
            'car_owner_phone' => [Rule::prohibitedIf($request->type == 'exist'), Rule::requiredIf($request->type == 'new'), 'nullable', 'string', 'max:255'],
            'car_owner_name' => ['nullable', 'string', 'max:255', Rule::prohibitedIf($request->type == 'exist')],
            'car_description' => ['nullable', 'string', 'max:255', Rule::prohibitedIf($request->type == 'exist')],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'price' => ['required', 'numeric'],
        ], [
            'car_id.prohibited' => __('responses.the car id field is prohibited when the type is new.'),
            'car_id.required' => __('responses.the car id field is required when the type is exist.'),
            'car_name.prohibited' => __('responses.the car name field is prohibited when the type is exist.'),
            'car_number.required' => __('responses.the car number field is required when the type is new.'),
            'car_number.prohibited' => __('responses.the car number field is prohibited when the type is exist.'),
            'car_letter.required' => __('responses.the car letter field is required when the type is new.'),
            'car_letter.prohibited' => __('responses.the car letter field is prohibited when the type is exist.'),
            'car_owner_phone.required' => __('responses.the car owner phone field is required when the type is new.'),
            'car_owner_phone.prohibited' => __('responses.the car owner phone field is prohibited when the type is exist.'),
            'car_owner_name.prohibited' => __('responses.the car owner name field is prohibited when the type is exist.'),
            'car_description.prohibited' => __('responses.the car description field is prohibited when the type is exist.'),
        ]);
        try {
            DB::beginTransaction();
            if ($request->type == 'new') {
                if (Car::where('car_number', $request->car_number)->where('car_letter', $request->car_letter)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => __('responses.the car number and letter already exists.'),
                    ], 400);
                }
                $car = Car::create([
                    'car_name' => $request->car_name,
                    'car_number' => $request->car_number,
                    'car_letter' => $request->car_letter,
                    'owner_phone' => $request->car_owner_phone,
                    'owner_name' => $request->car_owner_name,
                    'car_description' => $request->car_description,
                ]);
                $subscription = Subscription::create([
                    'car_id' => $car->id,
                    'location_id' => null,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'price' => $request->price,
                    'type' => 'car',
                ]);
            } elseif ($request->type == 'exist') {
                $car = Car::findOrFail($request->car_id);
                $subscription = Subscription::create([
                    'car_id' => $car->id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'price' => $request->price,
                    'type' => 'car',
                ]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('responses.subscription created successfully'),
                'subscription' => $subscription->load('car'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function storeLocationSubscription(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('create-subscriptions')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date'],
            'price' => ['required', 'numeric'],
        ]);
        try {
            DB::beginTransaction();
            $subscription = Subscription::create([
                'location_id' => $request->location_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'price' => $request->price,
                'type' => 'location',
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('responses.subscription created successfully'),
                'subscription' => $subscription->load('location'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function update(Request $request, $subscription_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('edit-subscriptions')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'start_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after:start_date'],
            'price' => ['sometimes', 'nullable', 'numeric'],
        ]);
        $subscription = Subscription::findOrFail($subscription_id);
        try {
            DB::beginTransaction();
            $subscription->update([
                'start_date' => $request->start_date ?? $subscription->start_date,
                'end_date' => $request->end_date ?? $subscription->end_date,
                'price' => $request->price ?? $subscription->price,
            ]);
            $subscription->load('car', 'location');
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('responses.subscription updated successfully'),
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('responses.error happened'),
            ], 400);
        }
    }

    public function destroy($subscription_id)
    {
        if (! Auth::guard('admins')->user()->hasPermission('delete-subscriptions')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $subscription = Subscription::findOrFail($subscription_id);
        $subscription->delete();
        return response()->json([
            'success' => true,
            'message' => __('responses.subscription deleted successfully'),
        ]);
    }
}
