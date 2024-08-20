<?php

use App\Http\Controllers\Api\AccredifyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/verify', [AccredifyController::class, 'verifyFile']);
});
