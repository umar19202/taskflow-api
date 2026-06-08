<?php

namespace App\Filters;

use App\Models\Task;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;

class TaskQueryFilter
{
    private Builder $query;

    public function __construct(private readonly array $filters)
    {
        $this->query = Task::query();
    }

    public static function apply(array $filters): Builder
    {
        return (new self($filters))->build();
    }

    private function build(): Builder
    {
        $this->filterByProject()
             ->filterByStatus()
             ->filterByPriority()
             ->filterByAssignee()
             ->filterByDueDate()
             ->withEagerLoads()
             ->applyOrdering();

        return $this->query;
    }

    private function filterByProject(): static
    {
        if ($projectId = $this->filters['project_id'] ?? null) {
            $this->query->where('project_id', $projectId);
        }
        return $this;
    }

    private function filterByStatus(): static
    {
        if ($status = $this->filters['status'] ?? null) {
            if (TaskStatus::tryFrom($status)) {
                $this->query->where('status', $status);
            }
        }
        return $this;
    }

    private function filterByPriority(): static
    {
        if ($priority = $this->filters['priority'] ?? null) {
            if (TaskPriority::tryFrom($priority)) {
                $this->query->where('priority', $priority);
            }
        }
        return $this;
    }

    private function filterByAssignee(): static
    {
        if ($assignee = $this->filters['assigned_to'] ?? null) {
            $this->query->where('assigned_to', $assignee);
        }
        return $this;
    }

    private function filterByDueDate(): static
    {
        if ($this->filters['overdue'] ?? false) {
            $this->query->whereNotNull('due_date')
                        ->where('due_date', '<', now()->toDateString())
                        ->whereNotIn('status', ['done', 'cancelled']);
        }
        return $this;
    }

    private function withEagerLoads(): static
    {
        $this->query->with(['creator:id,name', 'assignee:id,name,email']);
        return $this;
    }

    private function applyOrdering(): static
    {
        $sortBy  = in_array($this->filters['sort_by'] ?? null, ['due_date', 'priority', 'created_at'])
            ? $this->filters['sort_by']
            : 'created_at';

        $sortDir = ($this->filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $this->query->orderBy($sortBy, $sortDir);
        return $this;
    }
}
