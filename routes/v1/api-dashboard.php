<?php

use App\Http\Controllers\api\v1\admin\AdminController;
use App\Http\Controllers\api\v1\admin\AuthController;
use App\Http\Controllers\api\v1\admin\EmployeeController;
use App\Http\Controllers\api\v1\admin\CarController;
use App\Http\Controllers\api\v1\admin\LocationController;
use App\Http\Controllers\api\v1\admin\ProfileController;
use App\Http\Controllers\api\v1\admin\RoleController;
use App\Http\Controllers\api\v1\admin\SubscriptionController;
use App\Http\Controllers\api\v1\admin\ForgetPasswordController;
use App\Http\Controllers\api\v1\admin\TicketController;
use App\Http\Controllers\api\v1\admin\InvoiceController;
use App\Http\Controllers\api\v1\admin\ShiftController;
use App\Http\Controllers\api\v1\admin\ReportController;
use App\Http\Middleware\CheckForAdminStatus;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/dashboard/', 'middleware' => ['auth:admins', CheckForAdminStatus::class]], function () {

    // Auth Route
    Route::post('logout', [AuthController::class, 'logout']);

    // Profile Route
    Route::post('edit_profile', [ProfileController::class, 'editProfile']);
    Route::post('edit_password', [ProfileController::class, 'editPassword']);
    Route::get('profile', [ProfileController::class, 'show']);

    // Role Routes
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('roles/search', [RoleController::class, 'search']);
    Route::get('roles/all_permissions', [RoleController::class, 'allPermissionsForAdmin']);
    Route::get('roles/{role_id}', [RoleController::class, 'show']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::post('roles/{role_id}', [RoleController::class, 'update']);
    Route::delete('roles/{role_id}', [RoleController::class, 'destroy']);

    // Admin Routes
    Route::get('admins', [AdminController::class, 'index']);
    Route::get('admins/{admin_id}', [AdminController::class, 'show']);
    Route::post('admins', [AdminController::class, 'store']);
    Route::post('admins/{admin_id}', [AdminController::class, 'update']);
    Route::delete('admins/{admin_id}', [AdminController::class, 'destroy']);
    Route::post('admins/change_status/{admin_id}', [AdminController::class, 'changeStatus']);
    Route::post('admins/edit_password/{admin_id}', [AdminController::class, 'changePassword']);

    // Employee Routes
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::get('employees/{employee_id}', [EmployeeController::class, 'show']);
    Route::post('employees', [EmployeeController::class, 'store']);
    Route::post('employees/{employee_id}', [EmployeeController::class, 'update']);
    Route::delete('employees/{employee_id}', [EmployeeController::class, 'destroy']);
    Route::post('employees/change_status/{employee_id}', [EmployeeController::class, 'changeStatus']);
    Route::post('employees/edit_password/{employee_id}', [EmployeeController::class, 'changePassword']);
    Route::post('employees/assign_location/{employee_id}', [EmployeeController::class, 'assignLocation']);

    // Location Routes
    Route::get('locations', [LocationController::class, 'index']);
    Route::get('locations/search', [LocationController::class, 'search']);
    Route::get('locations/{location_id}', [LocationController::class, 'show']);
    Route::post('locations', [LocationController::class, 'store']);
    Route::post('locations/{location_id}', [LocationController::class, 'update']);
    Route::delete('locations/{location_id}', [LocationController::class, 'destroy']);

    // Car Routes
    Route::get('cars', [CarController::class, 'index']);
    Route::get('cars/search', [CarController::class, 'search']);

    // Subscription Routes
    Route::get('subscriptions', [SubscriptionController::class, 'index']);
    Route::get('subscriptions/{subscription_id}', [SubscriptionController::class, 'show']);
    Route::post('subscriptions/car', [SubscriptionController::class, 'storeCarSubscription']);
    Route::post('subscriptions/location', [SubscriptionController::class, 'storeLocationSubscription']);
    Route::post('subscriptions/{subscription_id}', [SubscriptionController::class, 'update']);
    Route::delete('subscriptions/{subscription_id}', [SubscriptionController::class, 'destroy']);

    // Ticket Routes
    Route::get('tickets', [TicketController::class, 'index']);
    Route::get('tickets/{ticket_id}', [TicketController::class, 'show']);

    // Invoice Routes
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{invoice_id}', [InvoiceController::class, 'show']);

    // Shift Routes
    Route::get('shifts', [ShiftController::class, 'index']);
    Route::get('shifts/{shift_id}', [ShiftController::class, 'show']);

    // Report Routes
    Route::get('reports', [ReportController::class, 'index']);
});

Route::group(['prefix' => 'v1/dashboard'], function () {

    // Auth Routes
    Route::post('login', [AuthController::class, 'login']);

    // Forget Password Routes
    Route::post('forget_password', [ForgetPasswordController::class, 'sendCodeForForgetPassword']);
    Route::post('set_otp_for_forget_password', [ForgetPasswordController::class, 'setOTPForForgetPassword']);
    Route::post('change_password', [ForgetPasswordController::class, 'changePassword']);
});
