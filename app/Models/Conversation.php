<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'title',
        'user_id',
        'type',
        'status',
        'assistant_type',
        'assistant_id',
        'pipeline_id',
        'stage_id',
        'pipeline_status',
        'active_tools',
        'system_messages',
        'system_message',
        'model',
        'prompt',
        'model_id',
    ];
    protected $casts = [
        'active_tools' => 'array',
        'system_messages' => 'array',
    ];
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }


    public function getPrompt(){

        $prompt = $this->messages()->where('role','prompt')->latest()->first();
        return $prompt;


    }

    public function getConversationMessages(){

        $messages = $this->messages()->orderBy('created_at')->get();


        // Format messages for Ollama API
        $formattedMessages = $messages->map(function ($message) {
            return [
                'role' => $message->role, // 'user', 'assistant', or 'system'
                'content' => $message->content,
            ];
        })->toArray();

        return $formattedMessages;

    }
    public function addMessage($role,$content){

        $message = Message::create([
            'conversation_id' => $this->id,
            'role'            => $role,
            'content'         => $content,
        ]);

        return $message->id;
    }

    /**
     * Get the messages for the conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the user that owns the conversation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stage()
    {

        return $this->hasOne(Stage::class,'id','stage_id')
            ->with(['assistants', 'successTool'])
            ->orderBy('order');

    }


    public function assistant()
    {
        return $this->hasOne(Assistant::class,'id','assistant_id');
    }


    public function getActiveToolsAttribute($value)
    {

        $active_tools = array();
        $toolIds = json_decode($value, true) ?? [];
        foreach ($toolIds as $toolId) {

           // echo $toolId;

            $active_tools[] = Tool::find($toolId);

        }

        return $active_tools;

        return Tool::whereIn('id', $toolIds)->get();
    }



    // Relationship with Pipeline
    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function getCurrentStage(){
        if($this->pipeline){

            if(!$this->stage_id){

                $stage = Stage::where('pipeline_id',$this->pipeline->id)->orderBy('order')->first();

            } else {

                $stage = $this->stage_id;

            }

            return $stage;
        }



        return false;



    }

    public function getNextStage()
    {
        if ($this->pipeline) {
            // Check if there is a current stage ID
            if ($this->stage_id) {
                // Find the current stage
                $currentStage = Stage::where('pipeline_id', $this->pipeline->id)
                    ->where('id', $this->stage_id)
                    ->first();

                if ($currentStage) {
                    // Find the next stage by order
                    $nextStage = Stage::where('pipeline_id', $this->pipeline->id)
                        ->where('order', '>', $currentStage->order)
                        ->orderBy('order')
                        ->first();

                    return $nextStage ?: false; // Return false if there's no next stage
                }
            }

            // If there's no current stage ID, return the first stage
            return Stage::where('pipeline_id', $this->pipeline->id)
                ->orderBy('order')
                ->first();
        }

        return false; // Return false if there's no pipeline
    }


    public function aiModel()
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }

    // Relationship with Model (AIModel)
    public function model()
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }








}
