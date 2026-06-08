<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function findById(int $id): ?Project
    {
        return Project::with(['owner:id,name,email,created_at'])->find($id);
    }

    public function create(array $data): Project
    {
        return Project::create($data);
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh(['owner:id,name,email,created_at']);
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Project::query()
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id));
            })
            ->with(['owner:id,name,email,created_at'])
            ->latest()
            ->paginate($perPage);
    }
}
