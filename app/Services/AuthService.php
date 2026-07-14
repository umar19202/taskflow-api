<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\UpdateProfileDTO;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function register(RegisterDTO $dto): array
    {
        $user = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        Cache::forget('users:all');

        $token = $user->createToken('api-token', ['*'])->plainTextToken;

        return compact('user', 'token');
    }

    public function login(LoginRequest $request): array
    {
        $user = $this->userRepository->findByEmail($request->validated('email'));

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $user->tokens()->delete();

        $token = $user->createToken('api-token', ['*'])->plainTextToken;

        return compact('user', 'token');
    }

    public function updateProfile(User $user, UpdateProfileDTO $dto): User
    {
        $data = $dto->toArray();

        if (empty($data)) {
            return $user;
        }

        $user = $this->userRepository->update($user, $data);

        Cache::forget('users:all');

        return $user;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
