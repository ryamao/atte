<?php

use App\Http\Controllers\StampController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/', [StampController::class, 'index'])->name('stamp');
    Route::post('/shift/begin', [StampController::class, 'storeShiftBegin'])->name('shift-begin');
    Route::post('/shift/end', [StampController::class, 'storeShiftTiming'])->name('shift-end');
    Route::post('/break/begin', [StampController::class, 'storeBreakBegin'])->name('break-begin');
    Route::post('/break/end', [StampController::class, 'storeBreakTiming'])->name('break-end');
});
