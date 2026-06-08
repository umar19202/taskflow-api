<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;

class ArchiveProjectAction
{
    public function handle(Project $project): Project
    {
        $project->update(['status' => 'archived']);

        Cache::tags(["user:{$project->owner_id}:projects"])->flush();

        return $project->fresh();
    }
}
