<?php




namespace App\Http\Controllers;

use App\Services\ToolExecutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CodingExecutor;
use Illuminate\Support\Facades\Log;

class ApiExecutorController extends Controller
{
    public function execute(Request $request, $executor, $method)
    {
        // Step 1: Authenticate the user via Sanctum
        $user = $request->user();

        Log::info('This is the Executor!');
        Log::info(json_encode($request->all()));
        Log::info($executor);
        Log::info($method);

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Step 2: Validate the executor and method
        $allowedExecutors = [

            'coding' => CodingExecutor::class,
            'tools' => ToolExecutor::class,
            'apiex' => ToolExecutor::class,
            // Add other executors like 'tools' => ToolExecutor::class here

        ];

        if (!array_key_exists($executor, $allowedExecutors)) {
            return response()->json(['error' => 'Executor not found'], 404);
        }

        $executorClass = $allowedExecutors[$executor];
        $executorInstance = new $executorClass();

        if (!method_exists($executorInstance, $method)) {
            return response()->json(['error' => 'Method not found'], 404);
        }

        // Step 3: Execute the method
        try {
            $arguments = $request->all();
            $executorInstance->auth_user_id = $user->id; // Pass authenticated user ID
            $response = $executorInstance->$method($arguments);
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
