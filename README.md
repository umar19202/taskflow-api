# TaskFlow API

TaskFlow API is a Laravel 12 REST backend for project management, built with a clean service, repository, DTO, and policy architecture.

## Overview

This repository contains the backend implementation for TaskFlow API. The current build includes:

- user authentication with Laravel Sanctum
- authenticated project CRUD operations
- task CRUD with filtering, status management, and priority
- request validation with Form Requests
- structured controller → service → repository flow
- DTO-based data transfer for create/update operations
- Redis-backed caching with Russian Doll pattern for task lists
- composable query filter chain for task listing
- domain events for async side effects
- action classes for single-purpose operations
- authorization using policies
- API resource responses with consistent JSON envelopes
- Docker-friendly Laravel setup

## Tech stack

- PHP 8.2
- Laravel 12
- Laravel Sanctum 4
- Redis (cache)
- MySQL-compatible database
- Docker / Docker Compose
- PHPUnit for testing
- Predis client for Redis

## Implemented features

### Authentication

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/profile`

Authentication is handled with Laravel Sanctum, and protected API routes require a valid bearer token.

### Projects module

- `GET /api/v1/projects`
- `POST /api/v1/projects`
- `GET /api/v1/projects/{id}`
- `PUT/PATCH /api/v1/projects/{id}`
- `DELETE /api/v1/projects/{id}`

Project requests are validated with `StoreProjectRequest` and `UpdateProjectRequest`, and updates use a DTO to pass only provided values.

### Tasks module

- `GET /api/v1/projects/{project}/tasks` — list tasks with filter, sort, and pagination
- `POST /api/v1/projects/{project}/tasks` — create a task
- `GET /api/v1/tasks/{task}` — show a single task (shallow route)
- `PUT/PATCH /api/v1/tasks/{task}` — update task status, priority, assignee, etc.
- `DELETE /api/v1/tasks/{task}` — soft delete a task

Tasks support the following filters via query parameters: `status`, `priority`, `assigned_to`, `overdue`, `sort_by`, `sort_dir`. Status changes fire a `TaskStatusChanged` domain event.

### Cache handling

Caching is implemented in `App\Services\ProjectService` and `App\Services\TaskService`:

- project list pages are cached using Redis tags per user
- single project retrieval is cached with a dedicated cache key
- cache entries are invalidated after create, update, and delete operations
- task list queries use Russian Doll caching — cache key embeds the project's `updated_at` timestamp, so any task write automatically busts all cached filter variants

## Architecture

The application follows a layered architecture with separation of concerns:

- `app/Http/Controllers/Api/V1/` — HTTP controllers that handle request routing and responses
- `app/Http/Requests/` — validation logic for incoming API data
- `app/DTOs/` — typed request payload objects for service methods
- `app/Services/` — business orchestration, caching, and event dispatching
- `app/Repositories/` — Eloquent persistence operations
- `app/Policies/` — authorization rules for model access
- `app/Filters/` — composable query filter chains (e.g., `TaskQueryFilter`)
- `app/Http/Resources/` — API response formatting
- `app/Support/ApiResponse.php` — standardized response wrapper
- `app/Support/Enums/` — PHP 8.1 backed enums for task status and priority
- `app/Actions/` — single-purpose action classes for complex operations

## Code structure highlights

### ProjectController

- accepts validated request data
- uses DTOs to map valid inputs
- delegates business logic to `ProjectService`
- returns JSON resource responses

### UpdateProjectDTO

- accepts optional `name`, `description`, and `status`
- converts incoming validated data into an array suitable for partial updates
- filters out null values so only supplied fields are updated

### ProjectService

- orchestrates project creation, update, delete, list, and retrieval
- controls cache invalidation for list and single project responses
- keeps controller logic thin and reusable

### ProjectRepository

- encapsulates Eloquent interactions for projects
- keeps query logic separated from service orchestration

## Project folder structure

Key directories used in this implementation:

- `app/Actions/` — single-purpose action classes
- `app/Contracts/Repositories/` — repository interfaces
- `app/DTOs/Project/` — request DTOs for project create/update
- `app/DTOs/Task/` — request DTOs for task create/update
- `app/Events/` — domain events
- `app/Filters/` — composable query filter chains
- `app/Http/Controllers/Api/V1/` — API controllers
- `app/Http/Requests/Project/` — project request validation classes
- `app/Http/Requests/Task/` — task request validation classes
- `app/Http/Resources/` — API JSON resources
- `app/Policies/` — model authorization policies
- `app/Providers/` — service provider registration
- `app/Repositories/` — data persistence
- `app/Services/` — domain logic and caching
- `app/Support/` — response helper utilities and enums

## Running locally

1. copy `.env.example` to `.env`
2. install PHP dependencies:
   ```bash
   composer install
   ```
3. generate application key:
   ```bash
   php artisan key:generate
   ```
4. configure database and Redis settings in `.env`
5. run migrations:
   ```bash
   php artisan migrate
   ```
6. start the application:
   ```bash
   php artisan serve
   ```

## Notes

This README describes the current implementation of TaskFlow API, including authentication and project management features. The repository is structured to highlight maintainability, clean separation of responsibilities, and API-first development.
