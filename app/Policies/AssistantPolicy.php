<?php

namespace App\Policies;

use App\Models\Assistant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssistantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assistants.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view assistants
    }

    /**
     * Determine whether the user can view the assistant.
     */
    public function view(User $user, Assistant $assistant): bool
    {
        // Public assistants can be viewed by anyone
        if ($assistant->is_public) {
            return true;
        }

        // Private assistants can only be viewed by their owner or system users
        return $user->id === $assistant->user_id || $user->hasRole('system');
    }

    /**
     * Determine whether the user can create assistants.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('system');
    }

    /**
     * Determine whether the user can update the assistant.
     */
    public function update(User $user, Assistant $assistant): bool
    {
        return $user->id === $assistant->user_id || $user->hasRole('system');
    }

    /**
     * Determine whether the user can delete the assistant.
     */
    public function delete(User $user, Assistant $assistant): bool
    {
        return $user->hasRole('system');
    }

    /**
     * Determine whether the user can manage assistants.
     */
    public function manageAssistants(User $user): bool
    {
        return $user->hasRole('system');
    }
} 