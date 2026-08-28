<?php

namespace App\Http\Controllers;

use App\Services\AiEasyFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiEasyFormController extends Controller
{
    protected $aiEasyFormService;

    public function __construct(AiEasyFormService $aiEasyFormService)
    {
        $this->aiEasyFormService = $aiEasyFormService;
    }

    /**
     * Initialize WebSocket connection for AI form assistance
     */
    public function initializeWebSocket(Request $request)
    {
        try {
            $validated = $request->validate([
                'formId' => 'required|string',
            ]);

            return $this->aiEasyFormService->initializeSession($validated['formId']);
        } catch (\Exception $e) {
            Log::error('Error initializing WebSocket: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to initialize WebSocket connection'], 500);
        }
    }

    /**
     * Handle incoming media stream data
     */
    public function handleMediaStream(Request $request)
    {
        try {
            $sessionId = $request->header('X-Session-ID');
            if (!$sessionId) {
                return response()->json(['error' => 'Session ID not provided'], 400);
            }

            $mediaData = $request->getContent();
            return $this->aiEasyFormService->processMediaStream($sessionId, $mediaData);
        } catch (\Exception $e) {
            Log::error('Error processing media stream: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process media stream'], 500);
        }
    }

    /**
     * End AI assistance session
     */
    public function endSession(Request $request)
    {
        try {
            $sessionId = $request->header('X-Session-ID');
            if (!$sessionId) {
                return response()->json(['error' => 'Session ID not provided'], 400);
            }

            $this->aiEasyFormService->endSession($sessionId);
            return response()->json(['message' => 'Session ended successfully']);
        } catch (\Exception $e) {
            Log::error('Error ending session: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to end session'], 500);
        }
    }

    public function storeForm(Request $request)
    {
        $validated = $request->validate([
            'formId' => 'required|string',
            'formData' => 'required|array',
            'formData.elements' => 'required|array'
        ]);

        $success = $this->aiEasyFormService->storeForm(
            $validated['formId'],
            $validated['formData']
        );

        return response()->json([
            'success' => $success
        ]);
    }

    public function updateElement(Request $request)
    {
        $validated = $request->validate([
            'formId' => 'required|string',
            'elementId' => 'required|string',
            'value' => 'required'
        ]);

        $success = $this->aiEasyFormService->updateFormElement(
            $validated['formId'],
            $validated['elementId'],
            $validated['value']
        );

        return response()->json([
            'success' => $success
        ]);
    }

    public function getElementValue($formId, $elementId)
    {
        $value = $this->aiEasyFormService->getElementValue($formId, $elementId);
        
        return response()->json([
            'value' => $value
        ]);
    }

    public function getAllFormValues($formId)
    {
        $values = $this->aiEasyFormService->getAllFormValues($formId);
        
        return response()->json($values);
    }
} 