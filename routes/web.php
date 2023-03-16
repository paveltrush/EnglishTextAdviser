<?php

use App\Http\Controllers\BotmanController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::match(['get', 'post'], '/botman', [BotmanController::class,'enterRequest']);

Route::get('/set-telegram-webhook',[TelegramController::class, 'setWebhook']);
Route::post('/'.config('telegram.bots.mybot.token').'/webhook', [TelegramController::class, 'handle']);
