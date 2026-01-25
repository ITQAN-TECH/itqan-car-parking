<?php

use App\Http\Controllers\api\v1\guests\TicketController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1/guests'], function () {

    Route::get('tickets/{ticket_number}', [TicketController::class, 'show']);
    Route::post('tickets/{ticket_number}/request', [TicketController::class, 'request']);
});
