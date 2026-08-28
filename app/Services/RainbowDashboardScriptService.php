<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RainbowDashboardScriptService
{


    public $conversation = null;



    public function executeScript($scriptId, $conversation = null)
    {
        $script = RainbowDashboardScript::find($scriptId);
        if(!$script){
            Log::error("[RainbowDashboardScriptService] Script not found: {$scriptId}");
            return null;
        }

        if($conversation){
            $this->conversation = $conversation;
        } else if($this->conversation) {
            $conversation = $this->conversation;
        } else {
            Log::error("[RainbowDashboardScriptService] No conversation provided");
            return null;
        }

        return $this->handleScript($script->name);
    }

    /**
     * Get the script handler for a given script name
     * 
     * @param string $scriptName
     * @param array $pathState
     * @return array|null
     */
    public function handleScript($scriptName)
    {
        if (!method_exists($this, $scriptName)) {
            Log::error("[RainbowDashboardScriptService] Script not found: {$scriptName}");
            return null;
        }

        try {
            return $this->$scriptName();
        } catch (\Exception $e) {
            Log::error("[RainbowDashboardScriptService] Error executing script {$scriptName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get available scripts
     * 
     * @return array
     */
    public function getAvailableScripts()
    {
        return [
            'get_ticket_summary' => 'Get a summary of ticket statistics',

        ];
    }


    public function  RainbowDashboardUserFromPhoneLookup($pathState)
    {


        $pathState = $this->conversation->path_state;

        if(isset($pathState['twilio_call']['Direction'])){
            $direction = $pathState['twilio_call']['Direction'];
            if($direction == 'outbound'){
                $phone = $pathState['twilio_call']['From'];
            } else {
                $phone = $pathState['twilio_call']['To'];
            }
        }

        Log::info('[RainbowDashboardScriptService]: RainbowDashboardUserFromPhoneLookup', [
            'phone' => $phone
        ]);

        $service = new RainbowDashboardTicketService();
        $user = $service->lookupUser($phone);

        if($user['success']){
            $user = $user['user'];
        } else {
            $user = null;
        }

       
        Log::info('[RainbowDashboardScriptService]: RainbowDashboardUserFromPhoneLookup', [
            'user' => $user
        ]);

        return $user;
    }



} 