<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function all(): Collection
    {
        return Cache::remember('users:all', 300, fn () => $this->userRepository->getAll()
        );
    }
}
