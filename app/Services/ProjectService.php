<?php

namespace App\Services;

use App\Actions\Project\ArchiveProjectAction;
use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\DTOs\Project\CreateProjectDTO;
use App\DTOs\Project\UpdateProjectDTO;
use App\Events\ProjectCreated;
use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function listForUser(User $user, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return Cache::tags(["user:{$user->id}:projects"])
            ->remember("page:{$page}", 300, fn() =>
                $this->projectRepository->paginateForUser($user, $perPage)
            );
    }

    public function findById(int $id): ?Project
    {
        $cacheKey = "project:{$id}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $project = $this->projectRepository->findById($id);

        if ($project) {
            Cache::put($cacheKey, $project, 300);
        }

        return $project;
    }

    public function create(User $user, CreateProjectDTO $dto): Project
    {
        $project = $this->projectRepository->create([
            'owner_id'    => $user->id,
            'name'        => $dto->name,
            'description' => $dto->description,
            'status'      => Project::STATUS_ACTIVE,
        ]);

        $this->clearListCache($user->id);

        event(new ProjectCreated($project, $user));

        return $project->load('owner:id,name,email,created_at');
    }

    public function update(Project $project, UpdateProjectDTO $dto): Project
    {
        $project = $this->projectRepository->update($project, $dto->toArray());

        if ($dto->status === 'archived') {
            (new ArchiveProjectAction)->handle($project);
        }

        $this->clearProjectCache($project->id);
        $this->clearListCache($project->owner_id);

        return $project;
    }

    public function delete(Project $project): void
    {
        $this->projectRepository->delete($project);

        $this->clearProjectCache($project->id);
        $this->clearListCache($project->owner_id);
    }

    private function clearListCache(int $userId): void
    {
        Cache::tags(["user:{$userId}:projects"])->flush();
    }

    private function clearProjectCache(int $projectId): void
    {
        Cache::forget("project:{$projectId}");
    }
}
