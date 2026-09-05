<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\NotificationController;

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
//通知
Route::middleware('api.key')->group(function () {
    //傳送通知
    Route::post('notifications', [NotificationController::class,'store']);
});