<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\SalesAppController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/sale_admin_login', [SalesAppController::class, 'login']);


Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/get_activity_overview_by_uid', [SalesAppController::class, 'getActivityOverview']);
    Route::get('/get_lead_list_by_uid', [SalesAppController::class, 'getLeadList']);
    Route::get('/get_sale_ddl_data', [SalesAppController::class, 'getSaleDdlData']);
    Route::get('/get_activity_detail', [SalesAppController::class, 'getActivityDetail']);
    Route::post('/post_lead_form_data', [SalesAppController::class, 'postLeadForm']);
    Route::get('/get_contracted_lead_lists_by_uid', [SalesAppController::class, 'getContractedLeadList']);
    Route::get('/get_contracted_detail', [SalesAppController::class, 'getContractedDetail']);
    Route::post('/post_contracted_data', [SalesAppController::class, 'postContractedData']);
});
