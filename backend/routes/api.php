<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationTemplateController;
use App\Http\Controllers\Api\DashboardStatsController;

/* apiResource會自動建立
    GET     /api/projects               index()
    POST    /api/projects               store()
    GET     /api/projects/{project}     show()
    PUT     /api/projects/{project}     update()
    PATCH   /api/projects/{project}     update()
    DELETE  /api/projects/{project}     destroy()
*/

//專案
Route::apiResource('projects', ProjectController::class);
//API Key(不可更新)
Route::apiResource('projects/{project}/api-keys', ApiKeyController::class)->except(['update']);
//API Key-重新產生 Key
Route::post('projects/{project}/api-keys/{apiKey}/regenerate', [ApiKeyController::class, 'regenerate']); 
//Templates
Route::apiResource('projects/{project}/templates', NotificationTemplateController::class);
//通知
Route::get('projects/{project}/notifications', [NotificationController::class, 'index']);
Route::get('projects/{project}/notifications/{notification}', [NotificationController::class, 'show']);


Route::middleware('api.key')->group(function () {
    //傳送通知
    Route::post('notifications', [NotificationController::class, 'store']);
    //重新發送
    Route::post('notifications/{notification}/retry', [NotificationController::class, 'retry']);

    //Dashboard
    Route::get('dashboard', [DashboardStatsController::class, 'index']);
});