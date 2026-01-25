<?php

use App\Http\Controllers\api\v1\employees\AuthController;
use App\Http\Controllers\api\v1\employees\ProfileController;
use App\Http\Controllers\api\v1\employees\notifications\database\DatabaseNotificationController;
use App\Http\Middleware\CheckForEmployeeStatus;
use App\Http\Controllers\api\v1\employees\FCMToken\FCMTokenController;
use App\Http\Controllers\api\v1\employees\ShiftController;
use App\Http\Controllers\api\v1\employees\CarController;
use App\Http\Controllers\api\v1\employees\TicketController;
use App\Http\Controllers\api\v1\supervisor\ReportController;
use App\Http\Middleware\CheckForEmployeeType;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/employees', 'middleware' => ['auth:employees', CheckForEmployeeStatus::class]], function () {

    // Auth Route
    Route::post('logout', [AuthController::class, 'logout']);

    // Profile Route
    Route::post('edit_profile', [ProfileController::class, 'editProfile']);
    Route::get('profile', [ProfileController::class, 'show']);
    Route::post('edit_password', [ProfileController::class, 'changePassword']);
    Route::get('employee_locations', [ProfileController::class, 'getEmployeeLocations']);

    // Database Notification Routes
    Route::get('notifications', [DatabaseNotificationController::class, 'index']);
    Route::get('notifications/mark_all_as_read', [DatabaseNotificationController::class, 'markAllAsRead']);
    Route::post('notifications/delete', [DatabaseNotificationController::class, 'delete']);
    Route::get('notifications/{id}', [DatabaseNotificationController::class, 'show']);
    Route::post('notifications/toggle', [DatabaseNotificationController::class, 'toggleNotifications']);

    // Change FCM Token
    Route::post('store_fcm_token', [FCMTokenController::class, 'store']);
    Route::delete('fcm_token', [FCMTokenController::class, 'destroy']);

    // Shift Routes
    Route::post('start_shift', [ShiftController::class, 'startShift']);
    Route::post('end_shift', [ShiftController::class, 'endShift']);

    // Car Routes
    Route::get('cars/search', [CarController::class, 'search']);
    Route::get('cars/{car_id}', [CarController::class, 'show']);
    Route::post('cars', [CarController::class, 'store']);
    Route::post('cars/{car_id}', [CarController::class, 'update']);

    // Ticket Routes
    Route::get('tickets', [TicketController::class, 'index']);
    Route::get('tickets/{ticket_id}', [TicketController::class, 'show']);
    Route::post('tickets', [TicketController::class, 'store']);
    Route::post('tickets/{ticket_id}/cancel', [TicketController::class, 'cancel']);
    Route::post('tickets/{ticket_id}/close', [TicketController::class, 'close']);

    Route::middleware([CheckForEmployeeType::class])->group(function () {
        Route::get('supervisor/reports', [ReportController::class, 'index']);
    });
});
Route::group(['prefix' => 'v1/employees/'], function () {

    // Auth Routes
    Route::post('login', [AuthController::class, 'login']);
});
