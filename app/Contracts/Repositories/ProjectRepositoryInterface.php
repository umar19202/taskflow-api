<?php

namespace App\Contracts\Repositories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function findById(int $id): ?Project;

    public function create(array $data): Project;

    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;

    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;
}
