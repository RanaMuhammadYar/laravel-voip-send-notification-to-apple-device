<?php

use App\Http\Controllers\NotificationContoller;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('send-notifications', [NotificationContoller::class,'index']);
