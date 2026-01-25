<?php

namespace App\Http\Controllers\api\v1\admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::guard('admins')->user()->hasPermission('show-reports')) {
            return response()->json([
                'success' => false,
                'message' => __('responses.forbidden'),
            ], 403);
        }
        $request->validate([
            'start_date' => 'sometimes|nullable|date_format:Y-m-d',
            'end_date' => 'sometimes|nullable|date_format:Y-m-d',
            'type' => 'sometimes|nullable|in:yearly,monthly,weekly,daily'
        ]);
        $start_date = $request->start_date ?? Carbon::parse(now())->startOfDay();
        $end_date = $request->end_date ?? Carbon::parse(now())->endOfDay();
        $type = $request->type ?? 'yearly'; // القيمة الافتراضية سنوي

        $data = [];
        $data['total_today_revenue'] = round(Invoice::whereBetween('created_at', [$start_date, $end_date])->sum('price'), 2);
        $data['total_locations_count'] = Location::count();
        $data['tickets'] = [];
        $data['tickets']['total_tickets_count'] = Ticket::count();
        $data['tickets']['total_active_tickets_count'] = Ticket::where('status', 'in_progress')->count();
        $data['tickets']['total_closed_tickets_count'] = Ticket::where('status', 'completed')->count();
        $data['tickets']['total_cancelled_tickets_count'] = Ticket::where('status', 'cancelled')->count();
        $data['tickets']['total_park_car_tickets_count'] = Ticket::where('type', 'park_car')->count();
        $data['tickets']['percentage_of_park_car_tickets'] = round(($data['tickets']['total_park_car_tickets_count'] / $data['tickets']['total_tickets_count']) * 100, 2);
        $data['tickets']['total_self_parking_tickets_count'] = Ticket::where('type', 'self_parking')->count();
        $data['tickets']['percentage_of_self_parking_tickets'] = round(($data['tickets']['total_self_parking_tickets_count'] / $data['tickets']['total_tickets_count']) * 100, 2);


        $data['employees'] = [];
        $data['employees']['total_employees_count'] = Employee::count();
        $data['employees']['total_employees_start_shift_count'] = Employee::whereHas('activeShift')->count();
        $data['employees']['percentage_of_employees_start_shift'] = round(($data['employees']['total_employees_start_shift_count'] / $data['employees']['total_employees_count']) * 100, 2);

        $data['revenue'] = [];
        $data['revenue']['total_revenue'] = round(Invoice::sum('price'), 2);
        $data['revenue']['total_park_car_revenue'] = round(Invoice::whereHas('ticket', function ($query) {
            $query->where('type', 'park_car');
        })->sum('price'), 2);
        $data['revenue']['percentage_of_park_car_revenue'] = round(($data['revenue']['total_park_car_revenue'] / ($data['revenue']['total_revenue'] == 0 ? 1 : $data['revenue']['total_revenue'])) * 100, 2);
        $data['revenue']['total_self_parking_revenue'] = round(Invoice::whereHas('ticket', function ($query) {
            $query->where('type', 'self_parking');
        })->sum('price'), 2);
        $data['revenue']['percentage_of_self_parking_revenue'] = round(($data['revenue']['total_self_parking_revenue'] / ($data['revenue']['total_revenue'] == 0 ? 1 : $data['revenue']['total_revenue'])) * 100, 2);

        // التحليل المالي حسب نوع التذكرة والفترة الزمنية
        $data['revenue_analysis'] = $this->getRevenueAnalysis($type);

        return response()->json([
            'success' => true,
            'message' => __('responses.report fetched successfully'),
            'data' => $data,
        ]);
    }

    private function getRevenueAnalysis($type)
    {
        $analysis = [];

        switch ($type) {
            case 'yearly':
                // آخر 6 سنوات
                $currentYear = Carbon::now()->year;
                for ($i = 5; $i >= 0; $i--) {
                    $year = $currentYear - $i;
                    $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
                    $endOfYear = Carbon::create($year, 12, 31)->endOfDay();

                    $parkCarRevenue = Invoice::whereHas('ticket', function ($query) {
                        $query->where('type', 'park_car');
                    })->whereBetween('created_at', [$startOfYear, $endOfYear])->sum('price');

                    $selfParkingRevenue = Invoice::whereHas('ticket', function ($query) {
                        $query->where('type', 'self_parking');
                    })->whereBetween('created_at', [$startOfYear, $endOfYear])->sum('price');

                    $analysis[] = [
                        'period' => (string)$year,
                        'park_car_revenue' => (float)round($parkCarRevenue, 2),
                        'self_parking_revenue' => (float)round($selfParkingRevenue, 2),
                        'total_revenue' => (float)round($parkCarRevenue + $selfParkingRevenue, 2),
                    ];
                }
                break;

            case 'monthly':
                // أشهر السنة الحالية
                $currentYear = Carbon::now()->year;
                for ($month = 1; $month <= 12; $month++) {
                    $startOfMonth = Carbon::create($currentYear, $month, 1)->startOfDay();
                    $endOfMonth = Carbon::create($currentYear, $month, 1)->endOfMonth()->endOfDay();

                    $parkCarRevenue = Invoice::whereHas('ticket', function ($query) {
                        $query->where('type', 'park_car');
                    })->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('price');

                    $selfParkingRevenue = Invoice::whereHas('ticket', function ($query) {
                        $query->where('type', 'self_parking');
                    })->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('price');

                    $analysis[] = [
                        'period' => Carbon::create($currentYear, $month, 1)->format('Y-m'),
                        'month_name' => Carbon::create($currentYear, $month, 1)->format('F'),
                        'park_car_revenue' => (float)round($parkCarRevenue, 2),
                        'self_parking_revenue' => (float)round($selfParkingRevenue, 2),
                        'total_revenue' => (float)round($parkCarRevenue + $selfParkingRevenue, 2),
                    ];
                }
                break;

            case 'weekly':
                // آخر 8 أسابيع
                for ($i = 7; $i >= 0; $i--) {
                    $startOfWeek = Carbon::now()->subWeeks($i)->startOfWeek();
                    $endOfWeek = Carbon::now()->subWeeks($i)->endOfWeek();

                    $parkCarRevenue = Invoice::whereHas('ticket', function ($query) {
                        $query->where('type', 'park_car');
                    })->whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('price');

                    $selfParkingRevenue = Invoice::whereHas('ticket', function ($query) {
                        $query->where('type', 'self_parking');
                    })->whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('price');

                    $analysis[] = [
                        'period' => $startOfWeek->format('Y-m-d') . ' to ' . $endOfWeek->format('Y-m-d'),
                        'week_number' => $startOfWeek->weekOfYear,
                        'park_car_revenue' => (float)round($parkCarRevenue, 2),
                        'self_parking_revenue' => (float)round($selfParkingRevenue, 2),
                        'total_revenue' => (float)round($parkCarRevenue + $selfParkingRevenue, 2),
                    ];
                }
                break;

            case 'daily':
                // آخر 7 أيام
                for ($i = 6; $i >= 0; $i--) {
                    $day = Carbon::now()->subDays($i);
                    $startOfDay = $day->copy()->startOfDay();
                    $endOfDay = $day->copy()->endOfDay();

                    $parkCarRevenue = Invoice::whereHas('ticket', function ($query) {
                        $query->where('type', 'park_car');
                    })->whereBetween('created_at', [$startOfDay, $endOfDay])->sum('price');

                    $selfParkingRevenue = Invoice::whereHas('ticket', function ($query) {
                        $query->where('type', 'self_parking');
                    })->whereBetween('created_at', [$startOfDay, $endOfDay])->sum('price');

                    $analysis[] = [
                        'period' => $day->format('Y-m-d'),
                        'day_name' => $day->format('l'),
                        'park_car_revenue' => (float)round($parkCarRevenue, 2),
                        'self_parking_revenue' => (float)round($selfParkingRevenue, 2),
                        'total_revenue' => (float)round($parkCarRevenue + $selfParkingRevenue, 2),
                    ];
                }
                break;
        }

        return $analysis;
    }
}
