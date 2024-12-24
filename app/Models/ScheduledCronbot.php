<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledCronbot extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scheduled_cronbots';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'assistant_id',
        'prompt',
        'is_repeating',
        'repeat_interval',
        'schedule',
        'next_run_at',
        'last_run_at',
        'end_at',
        'fail_tool_id',
        'success_tool_id',
        'pause_tool_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_repeating' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who owns the task.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the assistant assigned to the task.
     */
    public function assistant()
    {
        return $this->belongsTo(Assistant::class);
    }

    /**
     * Get the tool executed on failure.
     */
    public function failTool()
    {
        return $this->belongsTo(Tool::class, 'fail_tool_id');
    }

    /**
     * Get the tool executed on success.
     */
    public function successTool()
    {
        return $this->belongsTo(Tool::class, 'success_tool_id');
    }

    /**
     * Get the tool executed when paused.
     */
    public function pauseTool()
    {
        return $this->belongsTo(Tool::class, 'pause_tool_id');
    }

    /**
     * Check if the task is due to run based on its schedule and `next_run_at`.
     *
     * @return bool
     */
    public function isDue()
    {
        return $this->is_active && now()->greaterThanOrEqualTo($this->next_run_at);
    }

    /**
     * Deactivate the task.
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Schedule the next run for a repeating task.
     */
    public function scheduleNextRun()
    {
        if (!$this->is_repeating || !$this->schedule) {
            return;
        }

        // Example using a cron parser library (e.g., dragonmantank/cron-expression)
        $cron = \Cron\CronExpression::factory($this->schedule);
        $this->next_run_at = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        $this->save();
    }
}
