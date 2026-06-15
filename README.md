# TaskFlow API

TaskFlow API is a Laravel 12 REST backend for project management, built with a clean service, repository, DTO, and policy architecture.

## Project structure

```
taskflow-api/
├── .github/workflows/
│   ├── ci.yml                         # CI pipeline (Pint, PHPStan, PHPUnit)
│   └── deploy.yml                     # SSH deploy pipeline
├── app/
│   ├── Actions/
│   │   ├── Project/ArchiveProjectAction.php
│   │   └── Task/
│   │       ├── AssignTaskAction.php
│   │       └── ChangeTaskStatusAction.php
│   ├── Contracts/Repositories/        # Repository interfaces
│   │   ├── CommentRepositoryInterface.php
│   │   ├── ProjectRepositoryInterface.php
│   │   └── TaskRepositoryInterface.php
│   ├── DTOs/                          # Typed data transfer objects
│   │   ├── Auth/
│   │   ├── Comment/
│   │   ├── Project/
│   │   └── Task/
│   ├── Events/                        # Domain events
│   │   ├── CommentPosted.php
│   │   ├── ProjectCreated.php
│   │   ├── TaskCreated.php
│   │   └── TaskStatusChanged.php
│   ├── Filters/TaskQueryFilter.php    # Composable filter chain
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── HealthController.php
│   │   │   └── V1/
│   │   │       ├── AuthController.php
│   │   │       ├── CommentController.php
│   │   │       ├── NotificationController.php
│   │   │       ├── ProjectController.php
│   │   │       └── TaskController.php
│   │   ├── Middleware/
│   │   │   ├── ForceJsonResponse.php
│   │   │   ├── SecurityHeaders.php
│   │   │   └── SetRequestId.php
│   │   ├── Requests/                  # Form request validation
│   │   │   ├── Auth/
│   │   │   ├── Comment/
│   │   │   ├── Project/
│   │   │   └── Task/
│   │   └── Resources/                 # API JSON resources
│   │       ├── CommentResource.php
│   │       ├── ProjectResource.php
│   │       ├── TaskResource.php
│   │       └── UserResource.php
│   ├── Jobs/                          # Queued async jobs
│   │   ├── SendCommentNotification.php
│   │   └── SendTaskAssignedNotification.php
│   ├── Listeners/                     # Event listeners
│   │   ├── AddOwnerAsProjectMember.php
│   │   ├── NotifyCommentMentions.php
│   │   └── NotifyTaskAssignee.php
│   ├── Logging/AddRequestContext.php  # Structured JSON logging
│   ├── Models/                        # Eloquent models
│   │   ├── Comment.php
│   │   ├── Project.php
│   │   ├── Task.php
│   │   └── User.php
│   ├── Notifications/                 # Database notification blueprints
│   │   ├── CommentPostedNotification.php
│   │   └── TaskAssignedNotification.php
│   ├── Policies/                      # Authorization policies
│   │   ├── CommentPolicy.php
│   │   ├── ProjectPolicy.php
│   │   └── TaskPolicy.php
│   ├── Providers/AppServiceProvider.php
│   ├── Repositories/                  # Eloquent persistence
│   │   ├── CommentRepository.php
│   │   ├── ProjectRepository.php
│   │   └── TaskRepository.php
│   ├── Services/                      # Business logic & caching
│   │   ├── AuthService.php
│   │   ├── CommentService.php
│   │   ├── ProjectService.php
│   │   └── TaskService.php
│   └── Support/
│       ├── ApiResponse.php            # Standard JSON envelope
│       └── Enums/                     # TaskStatus, TaskPriority
├── bootstrap/app.php                  # Laravel 12 config (middleware, routing, exceptions)
├── config/                            # Application configuration
│   ├── cors.php
│   ├── logging.php
│   ├── queue.php
│   ├── sanctum.php
│   └── ...
├── database/
│   ├── factories/                     # Model factories (4)
│   ├── migrations/                    # Database migrations (8)
│   └── seeders/                       # Database seeders (5)
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile                 # Multi-stage build
├── docker-compose.yml                 # app, nginx, mysql, redis, queue
├── routes/
│   ├── api.php                        # API route definitions
│   ├── console.php
│   └── web.php
├── tests/
│   ├── Feature/                       # Feature tests
│   │   ├── Auth/
│   │   ├── Projects/
│   │   ├── Tasks/
│   │   └── Comments/
│   └── Unit/                          # Unit tests
│       ├── Policies/
│       └── Services/
├── .env.example
├── composer.json
├── phpunit.xml
├── phpstan.neon.dist
└── package.json
```

## Overview

This repository contains the backend implementation for TaskFlow API. The current build includes:

- user authentication with Laravel Sanctum (token-based, single-session)
- authenticated project CRUD operations with soft deletes
- task CRUD with filtering, status management, priority, and soft deletes
- request validation with Form Requests and policy authorization gates
- structured controller → service → repository flow
- DTO-based data transfer for create/update operations
- Redis-backed caching with Russian Doll pattern for task lists
- composable query filter chain for task listing
- domain events for async side effects (TaskCreated, TaskStatusChanged, CommentPosted)
- comment CRUD with author-only update/delete policy
- queued notifications for task assignment and comments via events, listeners, and jobs
- action classes for single-purpose operations (assign, change status, archive)
- authorization using policies (ProjectPolicy, TaskPolicy, CommentPolicy)
- API resource responses with consistent JSON envelopes
- health check endpoint monitoring database, Redis, cache, and queue
- rate limiting with Redis sliding window (per-group and per-verb)
- structured JSON logging with request context
- security headers (X-Content-Type-Options, X-Frame-Options, HSTS, etc.)
- Docker-friendly setup with multi-stage build
- CI/CD pipelines via GitHub Actions
- database seeding with factories and seeders

## Tech stack

- PHP 8.2
- Laravel 12
- Laravel Sanctum 4
- Redis (cache, queue, rate limiting, session)
- MySQL 8 compatible database
- Docker / Docker Compose
- PHPUnit for testing
- Predis client for Redis
- PHPStan (static analysis)
- Laravel Pint (code style)
- GitHub Actions (CI/CD)

## API response format

All API responses follow a consistent JSON envelope:

### Success response

```json
{
  "success": true,
  "message": "Task created successfully.",
  "data": { },
  "meta": {
    "request_id": "req_01HV8XYZ",
    "timestamp": "2026-01-15T10:30:00Z",
    "version": "v1"
  }
}
```

### Paginated response

```json
{
  "success": true,
  "message": "Tasks retrieved.",
  "data": [],
  "meta": {
    "request_id": "req_01HV8XYZ",
    "timestamp": "2026-01-15T10:30:00Z",
    "version": "v1",
    "pagination": {
      "total": 120,
      "per_page": 15,
      "current_page": 1,
      "last_page": 8
    }
  }
}
```

### Error response

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": { "title": ["The title field is required."] },
  "meta": {
    "request_id": "req_01HV8XYZ",
    "timestamp": "2026-01-15T10:30:00Z",
    "version": "v1"
  }
}
```

### HTTP status codes

| Scenario | Code |
|----------|------|
| Successful GET / list | 200 |
| Successful POST (created) | 201 |
| Successful DELETE | 204 |
| Validation error | 422 |
| Unauthenticated | 401 |
| Unauthorized (policy) | 403 |
| Not found | 404 |
| Too many requests | 429 |
| Server error | 500 |

## Implemented features

### Health endpoint

- `GET /api/health`

Checks database connectivity, Redis ping, cache store, and queue availability. Returns a `healthy` or `degraded` status with per-service checks. Unauthenticated, no rate limit — intended for load balancers.

### Authentication

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/profile`

Authentication is handled with Laravel Sanctum. Login uses single-session mode — all previous tokens are revoked on each new login. Protected routes require a valid bearer token in the `Authorization` header.

### Projects module

- `GET /api/v1/projects`
- `POST /api/v1/projects`
- `GET /api/v1/projects/{id}`
- `PUT/PATCH /api/v1/projects/{id}`
- `DELETE /api/v1/projects/{id}`

Project requests are validated with `StoreProjectRequest` and `UpdateProjectRequest`, and updates use a DTO to pass only provided values. Projects use soft deletes.

### Tasks module

- `GET /api/v1/projects/{project}/tasks` — list tasks with filter, sort, and pagination
- `POST /api/v1/projects/{project}/tasks` — create a task
- `GET /api/v1/tasks/{task}` — show a single task (shallow route)
- `PUT/PATCH /api/v1/tasks/{task}` — update task status, priority, assignee, etc.
- `DELETE /api/v1/tasks/{task}` — soft delete a task

Tasks support the following filters via query parameters: `status`, `priority`, `assigned_to`, `overdue`, `sort_by`, `sort_dir`. Status changes fire a `TaskStatusChanged` domain event. Tasks use soft deletes.

### Comments module

- `GET /api/v1/tasks/{task}/comments` — list comments for a task
- `POST /api/v1/tasks/{task}/comments` — create a comment
- `PUT/PATCH /api/v1/comments/{comment}` — update a comment (author only)
- `DELETE /api/v1/comments/{comment}` — delete a comment (author or project owner)

Comments use DB transactions with after-commit event dispatch. Creating a comment fires a `CommentPosted` domain event.

### Notifications module

- `GET /api/v1/notifications` — paginate notifications for the authenticated user
- `PATCH /api/v1/notifications/{id}/read` — mark a single notification as read
- `PATCH /api/v1/notifications/read-all` — mark all notifications as read

Notifications are delivered asynchronously via the `notifications` queue. Creating a task with an assignee fires a `TaskCreated` event, which dispatches a queued job to store a database notification for the assignee.

### Rate limiting

Redis-backed sliding window rate limiting with three named limiters:

| Limiter | Scope | Limit |
|---------|-------|-------|
| `auth` | Login & register endpoints | 10 requests/minute per IP |
| `writes` | POST, PUT, PATCH, DELETE | 30 requests/minute per user |
| `api` | All authenticated API routes | 60 requests/minute per user |

All rate limit violations return a standardized 429 JSON response.

### Middleware

| Middleware | Purpose |
|------------|---------|
| `ForceJsonResponse` | Forces `Accept: application/json` on all requests |
| `SetRequestId` | Generates a UUID per request, exposes as `X-Request-ID` header and `app('request_id')` container binding |
| `SecurityHeaders` | Adds `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `X-XSS-Protection`, and conditional HSTS headers |

### Structured logging

The `AddRequestContext` log processor enriches every log line with:

- `request_id` — propagated from HTTP context into queued jobs
- `user_id` — authenticated user identifier
- `ip` — request origin IP
- `env` — application environment

Logs are formatted as JSON, making them ingestible by Datadog, ELK Stack, or Papertrail.

### Caching

Caching is implemented in `App\Services\ProjectService`, `App\Services\TaskService`, and `App\Services\CommentService`:

- project list pages are cached using Redis tags per user
- single project retrieval is cached with a dedicated cache key
- cache entries are invalidated after create, update, and delete operations
- task list queries use Russian Doll caching — cache key embeds the project's `updated_at` timestamp, so any task or comment write automatically busts all cached filter variants
- comment writes touch the parent project, cascading invalidation to all related task caches

## Database

### Tables

| Table | Purpose |
|-------|---------|
| `users` | Authenticated actors with Sanctum token authentication |
| `projects` | Workspace containers owned by a user, with member pivot |
| `project_user` | Many-to-many pivot with `role` (member/admin) |
| `tasks` | Work units within projects, with status, priority, assignee, due date |
| `comments` | Threaded remarks attached to tasks |
| `notifications` | Database notification records for async delivery |
| `personal_access_tokens` | Sanctum API token storage |

### Factories

| Factory | Model |
|---------|-------|
| `UserFactory` | `App\Models\User` |
| `ProjectFactory` | `App\Models\Project` |
| `TaskFactory` | `App\Models\Task` |
| `CommentFactory` | `App\Models\Comment` |

### Seeders

Seeders run in order via `DatabaseSeeder`:

1. `UserSeeder` — creates sample users
2. `ProjectSeeder` — creates projects with owner assignments
3. `TaskSeeder` — creates tasks within projects with assignees
4. `CommentSeeder` — creates comments on tasks

Run with:

```bash
php artisan db:seed
```

## Events, listeners & jobs

| Event | Listener | Job | Queue |
|-------|----------|-----|-------|
| `TaskCreated` | `NotifyTaskAssignee` | `SendTaskAssignedNotification` | notifications |
| `TaskStatusChanged` | `NotifyTaskAssignee` | `SendTaskAssignedNotification` | notifications |
| `CommentPosted` | `NotifyCommentMentions` | `SendCommentNotification` | notifications |
| `ProjectCreated` | `AddOwnerAsProjectMember` | — | sync |

Events are fired from service classes after successful persistence. Listeners dispatch queued jobs for async side effects. The `request_id` is propagated from the HTTP context into each job for log traceability.

## Action classes

Single-purpose action classes encapsulate complex operations that don't justify a full service:

| Action | Purpose |
|--------|---------|
| `AssignTaskAction` | Assigns a user to a task with validation |
| `ChangeTaskStatusAction` | Transitions task status with policy checks |
| `ArchiveProjectAction` | Archives a project and cascades to tasks |

## Architecture

The application follows a layered architecture with separation of concerns:

- `app/Http/Controllers/Api/V1/` — HTTP controllers that handle request routing and responses
- `app/Http/Requests/` — validation logic for incoming API data
- `app/DTOs/` — typed request payload objects for service methods
- `app/Services/` — business orchestration, caching, and event dispatching
- `app/Repositories/` — Eloquent persistence operations (backed by interfaces in `Contracts/Repositories/`)
- `app/Policies/` — authorization rules for model access
- `app/Filters/` — composable query filter chains (e.g., `TaskQueryFilter`)
- `app/Http/Resources/` — API response formatting
- `app/Actions/` — single-purpose action classes for complex operations
- `app/Events/` — domain event value objects
- `app/Listeners/` — event handlers that dispatch queued jobs
- `app/Jobs/` — async units of work with retry and backoff
- `app/Notifications/` — database notification blueprints
- `app/Support/ApiResponse.php` — standardized response wrapper
- `app/Support/Enums/` — PHP 8.1 backed enums for task status and priority

## Testing

The test suite uses PHPUnit with a custom `FeatureTestCase` base class.

### Feature tests

- `tests/Feature/Auth/` — registration, login, logout, profile retrieval
- `tests/Feature/Projects/` — CRUD, authorization, pagination
- `tests/Feature/Tasks/` — CRUD, filtering, sorting, status transitions
- `tests/Feature/Comments/` — CRUD, author-only policies, task scoping

### Unit tests

- `tests/Unit/Services/` — service layer isolation tests
- `tests/Unit/Policies/` — authorization policy logic

Run tests:

```bash
composer test
```

## CI/CD

### CI pipeline (`.github/workflows/ci.yml`)

Triggered on push/PR to `main` and `develop`. Three parallel jobs:

| Job | Tool | Purpose |
|-----|------|---------|
| `code-style` | Laravel Pint | Enforces PSR-12 coding standards |
| `static-analysis` | PHPStan | Static analysis with larastan |
| `test-suite` | PHPUnit | Runs full test suite with code coverage |

### Deploy pipeline (`.github/workflows/deploy.yml`)

Triggered manually via `workflow_dispatch`. SSH-based deployment:

1. `php artisan down` — maintenance mode
2. `git pull origin main` — fetch latest code
3. `composer install --no-dev` — install production dependencies
4. `php artisan migrate --force` — run database migrations
5. Cache optimization (config, route, event, view)
6. `supervisorctl restart` — restart queue workers
7. `php artisan up` — bring application back online

## Docker

The project includes a full Docker Compose setup with multi-stage builds:

```bash
docker compose up -d
```

### Services

| Service | Image | Purpose |
|---------|-------|---------|
| `app` | Custom PHP 8.2-FPM | Laravel application server |
| `nginx` | nginx:alpine | HTTP reverse proxy on port 8000 |
| `mysql` | mysql:8.0 | Primary database |
| `redis` | redis:7-alpine | Cache, queue, session store (with AOF persistence) |
| `queue` | Custom PHP 8.2-FPM | Runs `php artisan queue:work` with `notifications,default` queues |

The PHP Dockerfile uses a multi-stage build — Composer dependencies are installed in a separate `vendor` stage and copied into the final runtime image, reducing image size and attack surface.

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

```
app/
├── Actions/                          # Single-purpose operation classes
│   ├── Project/
│   │   └── ArchiveProjectAction.php
│   └── Task/
│       ├── AssignTaskAction.php
│       └── ChangeTaskStatusAction.php
├── Contracts/Repositories/           # Repository interfaces
│   ├── CommentRepositoryInterface.php
│   ├── ProjectRepositoryInterface.php
│   └── TaskRepositoryInterface.php
├── DTOs/                             # Typed data transfer objects
│   ├── Auth/
│   ├── Comment/
│   ├── Project/
│   └── Task/
├── Events/                           # Domain events
├── Filters/                          # Composable query filter chains
├── Http/
│   ├── Controllers/Api/V1/          # API controllers
│   ├── Middleware/                   # ForceJson, SetRequestId, SecurityHeaders
│   ├── Requests/                    # Form request validation classes
│   └── Resources/                   # API JSON resources
├── Jobs/                             # Queued async jobs
├── Listeners/                        # Event listeners
├── Logging/                          # Structured JSON logging processor
├── Models/                           # Eloquent models
├── Notifications/                    # Database notification blueprints
├── Policies/                         # Authorization policies
├── Providers/                        # Service provider registration
├── Repositories/                     # Eloquent persistence
├── Services/                         # Business logic and caching
└── Support/                          # ApiResponse helper and enums
```

## Running locally

### Without Docker

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
6. seed the database (optional):
   ```bash
   php artisan db:seed
   ```
7. start the application:
   ```bash
   php artisan serve
   ```

### With Docker

```bash
docker compose up -d
```

This starts all services: app, nginx (port 8000), MySQL (port 3306), Redis (port 6379), and a queue worker. Run migrations and seeders inside the app container:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

## Notes

This README describes the current implementation of TaskFlow API, including authentication, project management, task management, and comments features. The repository is structured to highlight maintainability, clean separation of responsibilities, and API-first development.
