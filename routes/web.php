<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('test-email', function(Illuminate\Http\Request $request){
    dd(Mail::raw('Hi There! You Are Awesome.', function ($message) use ($request) {
        $message->to($request->to ?: 'vanak.roopak@gmail.com');
        $message->subject('Email Arrived');
    }));
});