<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function statsForUser(User $user): array
    {
        $cacheKey = "dashboard:user:{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            $projectIds = Project::where('owner_id', $user->id)
                ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
                ->pluck('id');

            $tasksQuery = Task::whereIn('project_id', $projectIds);

            return [
                'total_projects' => $projectIds->count(),
                'total_tasks' => $tasksQuery->count(),
                'active_tasks' => (clone $tasksQuery)
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->count(),
                'overdue_tasks' => (clone $tasksQuery)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->count(),
                'recent_tasks' => $this->getRecentTasks($projectIds),
                'tasks_by_project' => (clone $tasksQuery)
                    ->selectRaw('project_id, status, count(*) as count')
                    ->groupBy('project_id', 'status')
                    ->with('project:id,name')
                    ->get()
                    ->groupBy('project_id')
                    ->map(function ($rows) {
                        $first = $rows->first();

                        return [
                            'project_name' => $first->project->name,
                            'total' => (int) $rows->sum('count'),
                            'statuses' => $rows->mapWithKeys(fn ($r) => [$r->status->value => (int) $r->count])->toArray(),
                        ];
                    })
                    ->values(),
                'tasks_by_status' => (clone $tasksQuery)
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->mapWithKeys(fn ($r) => [$r->status->value => (int) $r->count])
                    ->toArray(),
                'tasks_by_priority' => (clone $tasksQuery)
                    ->selectRaw('priority, count(*) as count')
                    ->groupBy('priority')
                    ->get()
                    ->mapWithKeys(fn ($r) => [$r->priority->value => (int) $r->count])
                    ->toArray(),
            ];
        });
    }

    private function getRecentTasks(Collection $projectIds): array
    {
        return Task::whereIn('project_id', $projectIds)
            ->with(['project:id,name', 'assignee:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status->value,
                'priority' => $t->priority->value,
                'project_name' => $t->project->name,
                'assignee_name' => $t->assignee?->name,
                'created_at' => $t->created_at->toIso8601String(),
            ])
            ->toArray();
    }
}
