<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\EventLogController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\AssistantFunctionController;
use App\Http\Controllers\Auth\RegisteredUserController;
//use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoleController;

use App\Http\Controllers\ApiAssistantsController;
use App\Http\Controllers\OpenAiApiController;
use App\Http\Controllers\WebRTCController;
use App\Http\Controllers\VideoUploadController;

use App\Http\Controllers\BambooHRProxyController;

use App\Http\Controllers\SmsVerificationController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ApiFileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\OllamaStatusController;
use App\Http\Controllers\RemoteRichbotController;
use App\Http\Controllers\MediaTriggerController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\FileBrowserController;
use App\Http\Controllers\FileChangeRequestController;
use App\Http\Controllers\CodingController;
use App\Http\Controllers\SqlRequestController;
use App\Http\Controllers\CliRequestController;
use App\Models\ScheduledCronbot;
use App\Http\Controllers\ScheduledCronbotController;
use App\Http\Controllers\ApiExecutorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TwilioVoiceController;
use App\Http\Middleware\TwilioVerify;
use App\Http\Middleware\ValidateTwilioRequest;


// Twilio Voice Routes - group them together
Route::prefix('voice')->group(function () {
    
    Route::post('/incoming', [TwilioVoiceController::class, 'handleCall'])->name('voice');
    Route::post('/menu-response', [TwilioVoiceController::class, 'handleMenuResponse'])->name('menu-response');
    Route::post('/recording', [TwilioVoiceController::class, 'handleRecording'])->name('handle-recording');
    Route::post('/transcription', [TwilioVoiceController::class, 'handleTranscription'])->name('handle-transcription');
    Route::post('/voicemail', [TwilioVoiceController::class, 'handleVoicemail'])->name('handle-voicemail');
    Route::post('/tech-support', [TwilioVoiceController::class, 'techSupportOptions'])->name('tech-support-options');
    Route::post('/recording-status', [TwilioVoiceController::class, 'recordingStatus'])->name('recording-status');
    Route::post('/stream-status', [TwilioVoiceController::class, 'streamStatus'])->name('stream-status');
    Route::get('/token', [TwilioVoiceController::class, 'generateToken'])->name('voice.token');
});

Route::get('/display/{id}', [DisplayController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {



   



    Route::resource('integrations', IntegrationController::class);
    Route::prefix('sql-requests')->group(function () {
        Route::get('/', [SqlRequestController::class, 'index']);
        Route::patch('/approve/{id}', [SqlRequestController::class, 'approve']);
        Route::patch('/reject/{id}', [SqlRequestController::class, 'reject']);
    });

    Route::prefix('cli-requests')->group(function () {
        Route::get('/', [CliRequestController::class, 'index']);
        Route::patch('/approve/{id}', [CliRequestController::class, 'approve']);
        Route::patch('/reject/{id}', [CliRequestController::class, 'reject']);
    });


    Route::post('scheduled-cronbots/{scheduledCronbot}/trigger', [ScheduledCronbotController::class, 'trigger']);

        Route::get('/file-change-requests', [FileChangeRequestController::class, 'index']);
        Route::post('/file-change-requests/{id}/approve', [FileChangeRequestController::class, 'approve']);
        Route::post('/file-change-requests/{id}/reject', [FileChangeRequestController::class, 'reject']);







    Route::get('files', [FileBrowserController::class, 'browse']);
    Route::get('download', [FileBrowserController::class, 'download']);


    Route::post('/coding/session/create', [CodingController::class, 'createSession']);
    Route::post('/coding/start', [CodingController::class, 'startCoding']);

    Route::apiResource('pipelines', PipelineController::class);

    Route::delete('stages/{stage}', [StageController::class, 'destroy']);
    Route::put('stages/{stage}', [StageController::class, 'update']);
    Route::post('stages/{stage}/update_files', [StageController::class, 'update']);
    Route::delete('/stages/{stage}/files/{file}', [StageController::class, 'deleteFileFromStage']);
    Route::get('stages/{stage}', [StageController::class, 'show']);

    Route::prefix('pipelines/{pipeline}')->group(function () {

        Route::post('stages', [StageController::class, 'store']);
        Route::put('stage_assistants/{stage}', [StageController::class, 'update']);
        Route::put('stages/{stage}', [StageController::class, 'update']);
        //Route::put('stages/{stage}', [StageController::class, 'update']);
        //
        Route::delete('stages/{stage}', [StageController::class, 'destroy']);
        Route::get('stages/{stage}', [StageController::class, 'show']);
    });

    Route::post('pipelines/{pipeline}/stages/reorder', [PipelineController::class, 'updateOrder']);


    Route::post('/download-audio-stream', [AudioController::class, 'downloadAudioStream']);
    Route::post('/download-audio-stream/{id}', [AudioController::class, 'downloadAudioStream']);
    Route::post('/upload-audio-stream', [AudioController::class, 'uploadAudioStream']);
    Route::post('/upload-audio-stream/{id}', [AudioController::class, 'uploadAudioStream']);



    Route::apiResource('tools', ToolController::class);
    Route::delete('tools/{tool}/parameters/{parameter}', [ToolController::class, 'deleteParameter']);
    Route::get('/tools', [ToolController::class, 'index'])->name('tools.index');

    Route::get('/ollama_assistants', [AssistantController::class, 'index']);
    Route::get('/user_assistants', [AssistantController::class, 'index']);

    Route::get('/assistants/{id}', [AssistantController::class, 'show']);
    Route::post('/assistants', [AssistantController::class, 'store']);
    Route::put('/assistants/{id}', [AssistantController::class, 'update']);
    Route::post('/assistants/{id}/tools', [AssistantController::class, 'updateTools']);
    Route::delete('/assistants/{id}', [AssistantController::class, 'destroy']);

    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/activity', [DashboardController::class, 'getActivity']);
        Route::get('/upcoming-cronbots', [DashboardController::class, 'getUpcomingCronbots']);
    });

    Route::apiResource('contacts', ContactController::class);
    Route::post('contacts/{contact}/opt-in', [ContactController::class, 'optIn']);

});
Route::post('/login', [RemoteRichbotController::class, 'login']);

// Remote-Richbot Management
Route::middleware('auth:sanctum')->group(function () {


    Route::post('/remote-richbots/register', [RemoteRichbotController::class, 'registerDevice']);
    Route::get('/remote-richbots/{remote_richbot_id}/commands', [RemoteRichbotController::class, 'pollCommands']);
    Route::post('/remote-richbots/{remote_richbot_id}/media', [RemoteRichbotController::class, 'uploadMedia']);
    Route::post('/remote-richbots/{remote_richbot_id}/events', [RemoteRichbotController::class, 'receiveEvent']);

        Route::get('/remote-richbot/{id}', [RemoteRichbotController::class, 'show']);
        Route::put('/remote-richbot/command/{command}', [RemoteRichbotController::class, 'updateCommand']);
        Route::post('/remote-richbot/command/{command}', [RemoteRichbotController::class, 'updateCommand']);
        Route::post('/remote-richbot/{id}/send-command', [RemoteRichbotController::class, 'sendCommand']);



        // Media Trigger Routes
        Route::get('/remote-richbot/{richbotId}/triggers', [MediaTriggerController::class, 'index']);
        Route::post('/remote-richbot/{richbotId}/triggers', [MediaTriggerController::class, 'store']);
        Route::get('/remote-richbot/{richbotId}/triggers/{triggerId}', [MediaTriggerController::class, 'show']);
        Route::put('/remote-richbot/{richbotId}/triggers/{triggerId}', [MediaTriggerController::class, 'update']);
        Route::delete('/remote-richbot/{richbotId}/triggers/{triggerId}', [MediaTriggerController::class, 'destroy']);


    // Admin routes can be grouped and protected with additional middleware
});

Route::middleware('auth:sanctum')->group(function () {

    //Route::put('/scheduled-cronbots/{cronbot}', [ScheduledCronbotController::class, 'update']);

    Route::get('scheduled-cronbots', [ScheduledCronbotController::class, 'index'])->name('scheduled-cronbots.index');
    Route::post('scheduled-cronbots', [ScheduledCronbotController::class, 'store'])->name('scheduled-cronbots.store');
    Route::get('scheduled-cronbots/{scheduled_cronbot}', [ScheduledCronbotController::class, 'show'])->name('scheduled-cronbots.show');
    Route::put('scheduled-cronbots/{scheduled_cronbot}', [ScheduledCronbotController::class, 'update'])->name('scheduled-cronbots.update');
    Route::delete('scheduled-cronbots/{scheduled_cronbot}', [ScheduledCronbotController::class, 'destroy'])->name('scheduled-cronbots.destroy');


    Route::apiResource('appointments', AppointmentController::class);
    Route::get('/ollama/status', [OllamaStatusController::class, 'getStatus'])->name('ollama.status');

// Existing status route
    Route::post('/ollama/pull-model', [OllamaStatusController::class, 'pullModel']);

    Route::get('/models', [ModelController::class, 'index']);
    Route::post('/models', [ModelController::class, 'store']);
    Route::get('/models/{id}', [ModelController::class, 'show']);
    Route::put('/models/{id}', [ModelController::class, 'update']);
    Route::delete('/models/{id}', [ModelController::class, 'destroy']);


    Route::post('/conversations/assistant/{assistant}/start',[ConversationController::class, 'createAssistantConversation']);
    Route::post('/conversations/create', [ConversationController::class, 'createConversation']);
    Route::post('/conversations/pipeline_create/{pipeline}', [ConversationController::class, 'createPipelineConversation']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::post('/conversations/send-message', [ConversationController::class, 'sendMessage']);
    Route::get('/conversations/get-messages', [ConversationController::class, 'getMessages']);

    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'getMessages']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'sendMessage']);
    Route::post('/conversations/{id}/audio', [ConversationController::class, 'postAudio']);
    Route::get('/conversations/{id}/audio', [ConversationController::class, 'getAudio']);

    Route::post('/conversations/switch-assistant', [ConversationController::class, 'switchAssistant']);

    Route::post('/ollama/stable-diffusion/generate-image', [OllamaStatusController::class, 'generateImageStableDiffusion']);

    Route::delete('/ollama/delete-model', [OllamaStatusController::class, 'deleteModel']);
// New routes for additional functionalities
    Route::post('/ollama/create-model', [OllamaStatusController::class, 'createModel']);
    Route::post('/ollama/run-chat', [OllamaStatusController::class, 'runChat']);
    Route::post('/ollama/run-completion', [OllamaStatusController::class, 'runCompletion']);
    Route::post('/ollama/generate-image', [OllamaStatusController::class, 'generateImage']);
    Route::post('/ollama/vision-model', [OllamaStatusController::class, 'imageVision']);
    Route::post('/ollama/image-vision', [OllamaStatusController::class, 'imageVision']);
    Route::post('/ollama/generate-embeddings', [OllamaStatusController::class, 'generateEmbeddings']);
    Route::resource('projects', ProjectController::class);
    Route::resource('tasks', TaskController::class);

    // Route to display SMS messages (supports view and JSON)
    Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');

    // File Uploads
    Route::post('/upload', [ApiFileController::class, 'upload'])->name('uploadFile');
    Route::post('/upload/image', [ApiFileController::class, 'uploadImage'])->name('uploadImage');
    Route::post('/upload/url', [ApiFileController::class, 'uploadFromUrl'])->name('uploadFileFromUrl');

    Route::post('/file-transfer', [ApiFileController::class, 'transfer']);

    Route::get('/list/files', [ApiFileController::class, 'listFiles'])->name('listFiles');


    // File Download
    Route::get('/download', [ApiFileController::class, 'download'])->name('downloadFile');

    // File Deletion
    Route::delete('/delete', [ApiFileController::class, 'delete'])->name('deleteFile');

    // List Files and Folders

    Route::get('/list/folders', [ApiFileController::class, 'listFolders'])->name('listFolders');

    // List File and Folder Tree
    Route::get('/list/tree', [ApiFileController::class, 'listTree'])->name('listTree');

    // Directory Creation and Deletion
    Route::post('/directory/create', [ApiFileController::class, 'createDirectory'])->name('createDirectory');
    Route::delete('/directory/delete', [ApiFileController::class, 'deleteDirectory'])->name('deleteDirectory');

    // Text Writing and Appending to Files
    Route::post('/putText', [ApiFileController::class, 'putText'])->name('putText');
    Route::post('/appendText', [ApiFileController::class, 'appendText'])->name('appendText');

    // Send Email
    Route::post('/sendEmail', [ApiFileController::class, 'sendEmail'])->name('sendEmail');

});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/resend-email-verification', [EmailVerificationController::class, 'requestEmailVerificationToken']);
    Route::post('/verify-email', [EmailVerificationController::class, 'verifyEmailToken']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/resend-sms-verification', [SmsVerificationController::class, 'requestSmsVerificationToken']);
    Route::post('/verify-sms', [SmsVerificationController::class, 'verifySmsToken']);
});

//Route::post('/upload-video', [VideoUploadController::class, 'upload'])->name('upload.video');
Route::post('/upload-video', [VideoUploadController::class, 'upload'])->name('api.upload.video');
Route::post('/signal', [WebRTCController::class, 'signal']);
Route::post('/ice-candidate', [WebRTCController::class, 'handleIceCandidate']);

Route::middleware('auth:sanctum')->match(['get', 'post'], '/proxy/bamboohr/{endpoint}', [BambooHRProxyController::class, 'proxyRequest'])
    ->where('endpoint', '.*'); // Use a regular expression to allow any endpoint path

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/tokens', [ApiAuthController::class, 'getTokens']);
    Route::delete('/user/tokens/{tokenId}', [ApiAuthController::class, 'revokeToken']);
    Route::delete('/user/tokens', [ApiAuthController::class, 'revokeAllTokens']);
});

Route::middleware('auth:sanctum')->get('/user',[UserController::class, 'show']);

// Auth routes
Route::post('/login', [ApiAuthController::class, 'login'])->name('api.login');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('api.register');

Route::middleware('auth:sanctum')->post('/logout', [ApiAuthController::class, 'logout'])->name('api.logout');
// Content routes
Route::middleware('auth:sanctum')->get('/content/{section}', [ContentController::class, 'getContent'])->name('api.content.get');

// User management routes
Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('users')->group(function () {
    Route::post('/', [UserController::class, 'store'])->name('api.users.store');
    Route::put('/{user}', [UserController::class, 'update'])->name('api.users.update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('api.users.destroy');
});
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/roles', [RoleController::class, 'index']); // Get list of all roles
    Route::post('/users/{user}/roles', [RoleController::class, 'updateUserRoles']);

    Route::get('/users', [UserController::class, 'getUsers'])->name('api.users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('api.users.show');
});

// Event log routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/eventlogs', [EventLogController::class, 'index'])->name('api.eventlogs.index');
    Route::get('/eventlogs/{eventLog}', [EventLogController::class, 'show'])->name('api.eventlogs.show');
});

// Password and email verification routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->name('api.verification.send');
    Route::put('/password/update', [ProfileController::class, 'updatePassword'])->name('api.password.update');
});

// Assistant functions routes
Route::middleware('auth:sanctum')->get('/assistant_functions', [AssistantFunctionController::class, 'index'])->name('api.assistant_functions.index');

// Assistant management routes
Route::middleware('auth:sanctum')->prefix('assistants')->group(function () {
    Route::get('/', [ApiAssistantsController::class, 'index'])->name('api.assistants.index');
   // Route::post('/', [ApiAssistantsController::class, 'store'])->name('api.assistants.store');
    Route::delete('/{assistant}', [ApiAssistantsController::class, 'destroy'])->name('api.assistants.destroy');
});

// OpenAI API interaction routes

Route::middleware('auth:sanctum')->prefix('openai')->group(function () {

        Route::get('/thread-info', [OpenAiApiController::class, 'getThreadInfo']);

        Route::post('/easy-mode', [OpenAiApiController::class, 'easyMode'])->name('api.openai.easy-mode');

    Route::get('/list-files', [OpenAiApiController::class, 'listFiles'])->name('api.openai.listFiles');

        Route::post('/create-thread', [OpenAiApiController::class, 'createThread'])->name('api.openai.createThread');
        Route::post('/generate-image', [OpenAiApiController::class, 'generateAndStoreImage']);

        Route::post('/send-request', [OpenAiApiController::class, 'sendMessage'])->name('api.openai.sendRequest');

    Route::get('/list-assistants', [ApiAssistantsController::class, 'listAssistants'])->name('api.openai.listAssistants');
    Route::post('/send-message', [OpenAiApiController::class, 'sendMessage'])->name('api.openai.sendMessage');
    Route::get('/get-updates/{thread_id}', [OpenAiApiController::class, 'getUpdates'])->name('api.openai.getUpdates');

});



// Catch-all route for executors
//Route::get('/api_ex/get_weather', [ApiExecutorController::class, 'execute']);
//Route::get('/get_weather', [ApiExecutorController::class, 'execute']);
//
    //->middleware('auth:sanctum');




// Catch-all route for executors
Route::any('/{executor}/{method}', [ApiExecutorController::class, 'execute'])
    ->where('executor', '[a-zA-Z0-9]+')
    ->where('method', '[a-zA-Z0-9_]+')
    ->middleware('auth:sanctum');

Route::get('/conversations', [ConversationController::class, 'index']);

/*
Route::post('/login', [ApiAuthController::class, 'login']);
Route::post('/register', [RegisteredUserController::class, 'store'])->name('api.register');

Route::middleware('auth:sanctum')->post('/logout', [ApiAuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/content/{section}', [ContentController::class, 'getContent'])->name('api.content.get');

// Add more API routes as needed
Route::middleware('auth:sanctum')->get('/user',[UserController::class, 'show']);
Route::middleware('auth:sanctum')->get('/assistant_functions',[AssistantFunctionController::class, 'index'])->name('api.assistant_functions.index');
//Route::middleware('auth:sanctum')->resource('assistant_functions', AssistantFunctionController::class);



Route::middleware('auth:sanctum')->group(function () {
    //Route::resource('assistant_functions', AssistantFunctionController::class);
    //Route::apiResource('assistant_functions', AssistantFunctionController::class);
    Route::get('/chat/list-files', [ChatController::class, 'listFiles'])->name('api.chat.listFiles');

// Route to list available assistants
    Route::get('/chat/list-assistants', [ChatController::class, 'listAssistants'])->name('api.chat.listAssistants');

// Route to handle the submission of the selected data
    Route::post('/chat/send-request', [ChatController::class, 'sendRequest'])->name('api.chat.sendRequest');



});


Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('users')->group(function () {

    Route::post('/', [UserController::class, 'store']);
    Route::put('/{user}', [UserController::class, 'update']);
    Route::delete('/{user}', [UserController::class, 'destroy']);

});







Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/eventlogs', [EventLogController::class, 'index'])->name('api.eventlogs.index');
    Route::get('/eventlogs/{eventLog}', [EventLogController::class, 'show'])->name('api.eventlogs.show');
    Route::get('/users', [UserController::class, 'getUsers'])->name('api.users.index');

});

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->name('api.verification.send');
    Route::put('/password/update', [ProfileController::class, 'updatePassword'])->name('api.password.update');

});
Route::middleware(['auth:sanctum'])->prefix('users')->group(function () {

   // Route::get('/', [UserController::class, 'index']);
    Route::get('/{user}', [UserController::class, 'show']);


});

*/
