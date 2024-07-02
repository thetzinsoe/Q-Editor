<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::prefix('video')->group(function(){
    Route::get('/',[VideoController::class,'index']);
    Route::post('/edit-video', [VideoController::class, 'edit']);
    Route::post('/upload-video', [VideoController::class, 'upload']);
});
