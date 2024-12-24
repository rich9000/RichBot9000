<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AudioController;

use App\Http\Controllers\ContentController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\EventLogController;
use App\Http\Controllers\AssistantFunctionController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\RichbotController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\TwilioVoiceController;
use App\Http\Middleware\TwilioVerify;


Route::get('/', function () {
    return view('welcome');
});
Route::post('/sms/reply', [SmsController::class, 'handleReply']);

Route::get('/richbot9000', [RichbotController::class, 'show']);

Route::post('/richbot9000', [RichbotController::class, 'post']);

Route::get('/display/{id}', [DisplayController::class, 'show']);

Route::get('/notifications', function () {
    return view('notifications');
});

Route::get('/pwa', function () {
    return view('pwa');
});
//2bf9c3a721af4e37af476da8e1a866ffd076bf87 richbot9000 api bamboohr
Route::get('/webapp', function () {
    return view('webapp.webapp');
});
Route::get('/dashboard', function () {

    return view('dashboard');

})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware(['auth'])->group(function () {


    Route::resource('assistant_functions', AssistantFunctionController::class);


    Route::post('/chat/easy-mode', [ChatController::class, 'easyMode'])->name('chat.easy-mode');
    Route::post('/chat/easy-mode/{id}', [ChatController::class, 'easyModeUpdate'])->name('chat.easy-mode.update');


    Route::get('/chat/updates', [ChatController::class, 'getUpdates']);

    Route::get('/record-audio', [AudioController::class, 'index'])->name('audio.index');
    Route::post('/upload-audio', [AudioController::class, 'upload'])->name('audio.upload');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/files', [ChatController::class, 'filesDashboard'])->name('chat.files.index');
    Route::get('/chat/assistants', [ChatController::class, 'assistantsDashboard'])->name('chat.assistants.index');
    Route::delete('/chat/functions/{id}', [ChatController::class, 'deleteFunction']);
    Route::post('/chat/functions', [ChatController::class, 'storeFunction']);
    Route::get('/chat/functions/json', [ChatController::class, 'functions']);
    Route::get('/chat/functions', [ChatController::class, 'functionsIndex']);

    Route::get('/chat/dashboard', [ChatController::class, 'dashboard'])->name('chat.dashboard');
    Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/upload-file', [ChatController::class, 'uploadFiles'])->name('chat.upload');
    Route::post('/chat/session/destroy', [ChatController::class, 'newSession'])->name('chat.session.new');
    Route::delete('/chat/file/{id}', [ChatController::class, 'deleteFile'])->name('chat.files.delete');

    Route::post('/chat/assistant/store', [ChatController::class, 'storeAssistant'])->name('chat.assistants.store');
    Route::delete('/chat/assistant/{id}', [ChatController::class, 'deleteAssistant'])->name('chat.assistants.delete');
    Route::delete('/chat/assistants', [ChatController::class, 'getAssistants'])->name('chat.assistants.get');



// Route to list files based on a root directory
    Route::get('/chat/list-files', [ChatController::class, 'listFiles'])->name('chat.listFiles');

// Route to list available assistants
    Route::get('/chat/list-assistants', [ChatController::class, 'listAssistants'])->name('chat.listAssistants');

// Route to handle the submission of the selected data
    Route::post('/chat/send-request', [ChatController::class, 'sendRequest'])->name('chat.sendRequest');

























    Route::get('/content/{section}', [ContentController::class, 'getContent'])->name('content.get');

    Route::get('/eventlogs', [EventLogController::class, 'index'])->name('eventlogs.index');
    Route::get('/eventlogs/{eventLog}', [EventLogController::class, 'show'])->name('eventlogs.show');
});

Route::middleware(['auth', CheckRole::class.':Admin'])->group(function () {

    Route::resource('users', UserController::class)->except(['show', 'index']);

});


Route::middleware('auth')->group(function () {


    Route::resource('users', UserController::class)->only(['show', 'index']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Twilio Voice Routes
Route::post('/voice/incoming', [TwilioVoiceController::class, 'handleCall'])
    ->middleware('twilio.verify');

Route::get('/twilio/token', [TwilioVoiceController::class, 'generateToken']);

require __DIR__.'/auth.php';
