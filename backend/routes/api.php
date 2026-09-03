<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;

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
