<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'title',
        'user_id',
        'type',
        'room',
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
        'phone_tree_call_id',
        'conversation_path_id',
        'current_node_index',
        'path_state'
    ];
    protected $casts = [
        'active_tools' => 'array',
        'system_messages' => 'array',
        'path_state' => 'json',
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

    //we cant overload it, we ave to get the whole path_state and add to it
    public function addToPathState($key, $value){
        $path_state = $this->path_state;
        $path_state[$key] = $value;
        $this->path_state = $path_state;
        $this->save();
    }

    public function getPathState(){
        return $this->path_state;
    }
    public function getPathStateByKey($key){
        return $this->path_state[$key] ?? null;
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

    public function phoneTreeCall()
    {
        return $this->belongsTo(PhoneTreeCall::class);
    }

    public function conversationPath(): BelongsTo
    {
        return $this->belongsTo(ConversationPath::class);
    }








}
