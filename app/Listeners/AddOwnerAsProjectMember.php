<?php

namespace App\Listeners;

use App\Events\ProjectCreated;

class AddOwnerAsProjectMember
{
    public function handle(ProjectCreated $event): void
    {
        $event->project->members()->syncWithoutDetaching([
            $event->owner->id => ['role' => 'admin'],
        ]);
    }
}
