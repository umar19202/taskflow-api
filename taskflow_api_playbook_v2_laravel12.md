# TaskFlow API — Engineering Playbook
### Production-Grade Laravel 12 Backend · SaaS Architecture Reference

> **Document Type:** Execution-Ready Engineering Playbook  
> **Stack:** Laravel 12 · PHP 8.2+ · MySQL 8 · Redis 7 · Sanctum 4 · Docker · PHPUnit · GitHub Actions  
> **Audience:** Senior engineers, technical recruiters, portfolio evaluation  
> **Version:** 2.0.0 · Laravel 12 Edition

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Architecture Overview](#2-architecture-overview)
3. [Folder Structure Strategy](#3-folder-structure-strategy)
4. [API Design Standards](#4-api-design-standards)
5. [Phase 1 — Project Setup](#5-phase-1--project-setup)
6. [Phase 2 — Authentication](#6-phase-2--authentication)
7. [Phase 3 — Projects Module](#7-phase-3--projects-module)
8. [Phase 4 — Tasks Module](#8-phase-4--tasks-module)
9. [Phase 5 — Comments Module](#9-phase-5--comments-module)
10. [Phase 6 — Notifications System](#10-phase-6--notifications-system)
11. [Phase 7 — Testing Strategy](#11-phase-7--testing-strategy)
12. [Phase 8 — Postman Collection Strategy](#12-phase-8--postman-collection-strategy)
13. [Phase 9 — CI/CD Pipeline](#13-phase-9--cicd-pipeline)
14. [Phase 10 — Deployment Strategy](#14-phase-10--deployment-strategy)
15. [Phase 11 — Database Seeding & Factories](#15-phase-11--database-seeding--factories)
16. [Final Commit History](#16-final-commit-history)
17. [Appendix A — Database Index Summary](#appendix-a--database-index-summary)
18. [Appendix B — N+1 Prevention Rules](#appendix-b--n1-prevention-rules)
19. [Appendix C — Redis Cache Key Strategy](#appendix-c--redis-cache-key-strategy)
20. [Appendix D — Senior Engineer Interview Talking Points](#appendix-d--senior-engineer-interview-talking-points)

---

# 1. Project Overview

## What Is TaskFlow API?

TaskFlow API is a RESTful SaaS backend providing project and task management capabilities. It is a portfolio-grade system demonstrating production engineering competency: authentication, policy-based authorization, event-driven architecture, queue-based notifications, Redis caching, structured logging, and full CI/CD — all written in Laravel 12 conventions.

## Business Domain

| Entity | Description |
|--------|-------------|
| **User** | Authenticated actor. Can own or be a member of projects. |
| **Project** | Workspace container. Has an owner and optional members. |
| **Task** | Work unit inside a project. Has status, priority, assignee, due date. |
| **Comment** | Threaded remarks attached to a task. |
| **Notification** | System-generated event messages delivered asynchronously. |

## Design Philosophy

- Controllers are thin dispatchers — no business logic inside them.
- Business logic lives exclusively in **Service classes** or **Action classes**.
- Data is passed between layers using **DTOs** (Data Transfer Objects).
- Complex queries are isolated in **Query Builder / Filter classes**, not inline `when()` chains.
- All side effects (notifications, auditing) are fired via **Events + Listeners** so the domain layer stays clean.
- Every failure path — validation, auth, 404, server error — flows through a single **exception renderer** configured in `bootstrap/app.php`, producing a uniform JSON envelope.
- The event structure (`TaskCreated`, `TaskStatusChanged`, `CommentPosted`) is designed to be forward-compatible with event sourcing patterns — each event is a plain, serializable value object.

## Laravel 12 Key Differences From Laravel 11

| Area | Laravel 11 | Laravel 12 |
|------|-----------|-----------|
| Exception handling | `app/Exceptions/Handler.php` | `->withExceptions()` in `bootstrap/app.php` |
| Middleware registration | `app/Http/Kernel.php` | `->withMiddleware()` in `bootstrap/app.php` |
| API routing | Separate provider | `api: __DIR__.'/../routes/api.php'` in `withRouting()` |
| Event registration | `EventServiceProvider` | Auto-discovery or `AppServiceProvider::boot()` |
| Resource namespace | `app/Resources/` (v10 style) | `app/Http/Resources/` (correct location) |
| CORS | Middleware | `config/cors.php` |

---

# 2. Architecture Overview

## Layered Architecture

```
HTTP Request
    │
    ▼
[ Middleware Stack ]           bootstrap/app.php → withMiddleware()
  - ForceJsonResponse
  - SetRequestId
  - CORS (config/cors.php)
  - Auth (Sanctum)
  - Rate Limiting (Redis sliding window)
    │
    ▼
[ Form Request ]               app/Http/Requests/
  - Validation rules
  - Policy authorization gate
    │
    ▼
[ Controller ]                 app/Http/Controllers/Api/V1/
  - Builds DTO from validated data
  - Delegates to Service or Action
  - Returns ApiResponse
    │
    ▼
[ DTO ]                        app/DTOs/
  - Typed, readonly value object
  - No behavior — pure data carrier
    │
    ▼
[ Service / Action ]           app/Services/ or app/Actions/
  - Business logic & orchestration
  - Calls Repository for data
  - Calls QueryFilter for listing
  - Fires Events for side effects
  - Reads/writes Redis cache
    │
    ├──▶ [ Repository ]        app/Repositories/
    │       └── Eloquent query abstraction (reused queries only)
    │
    ├──▶ [ QueryFilter ]       app/Filters/
    │       └── Composable filter chain for task listing
    │
    ├──▶ [ Event Dispatcher ]
    │         │
    │         ▼
    │    [ Listeners ]  ──── onQueue() ────▶  [ Jobs ]
    │                                           └── Notify assignee
    │                                           └── Send comment alert
    └──▶ [ Cache (Redis) ]
    │
    ▼
[ API Resource ]               app/Http/Resources/
  - Model → safe JSON array
    │
    ▼
[ ApiResponse Wrapper ]        app/Support/ApiResponse.php
  - Standard envelope with meta
    │
    ▼
HTTP Response
```

## Component Map

| Component | Namespace | Responsibility |
|-----------|-----------|---------------|
| `FormRequest` | `App\Http\Requests` | Input validation + policy gate |
| `DTO` | `App\DTOs` | Typed data carrier between controller and service |
| `Controller` | `App\Http\Controllers\Api\V1` | Builds DTO, calls service, returns response |
| `Service` | `App\Services` | Orchestrates repositories, fires events, caches |
| `Action` | `App\Actions` | Single-purpose operation (alternative to fat services) |
| `QueryFilter` | `App\Filters` | Composable filter chain for complex list queries |
| `Repository` | `App\Repositories` | Eloquent query abstraction for reused queries |
| `Event` | `App\Events` | Serializable value object — something that happened |
| `Listener` | `App\Listeners` | Reacts to event; dispatches async Job |
| `Job` | `App\Jobs` | Single unit of async work with retry + backoff |
| `Resource` | `App\Http\Resources` | Model → API-safe JSON shape |
| `Policy` | `App\Policies` | Authorization rules per model |
| `ApiResponse` | `App\Support` | Uniform JSON envelope builder |

---

# 3. Folder Structure Strategy

## Full Directory Layout

```
taskflow-api/
├── app/
│   ├── Actions/                          ← Single-purpose operations
│   │   ├── Task/
│   │   │   ├── AssignTaskAction.php
│   │   │   └── ChangeTaskStatusAction.php
│   │   └── Project/
│   │       └── ArchiveProjectAction.php
│   ├── DTOs/                             ← Typed data transfer objects
│   │   ├── Auth/
│   │   │   └── RegisterDTO.php
│   │   ├── Project/
│   │   │   ├── CreateProjectDTO.php
│   │   │   └── UpdateProjectDTO.php
│   │   └── Task/
│   │       ├── CreateTaskDTO.php
│   │       └── UpdateTaskDTO.php
│   ├── Events/
│   │   ├── TaskCreated.php
│   │   ├── TaskStatusChanged.php
│   │   └── CommentPosted.php
│   ├── Filters/                          ← Query filter / builder pattern
│   │   └── TaskQueryFilter.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── AuthController.php
│   │   │           ├── ProjectController.php
│   │   │           ├── TaskController.php
│   │   │           ├── CommentController.php
│   │   │           └── NotificationController.php
│   │   ├── Middleware/
│   │   │   ├── SetRequestId.php
│   │   │   ├── ForceJsonResponse.php
│   │   │   └── SecurityHeaders.php
│   │   ├── Requests/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginRequest.php
│   │   │   │   └── RegisterRequest.php
│   │   │   ├── Project/
│   │   │   │   ├── StoreProjectRequest.php
│   │   │   │   └── UpdateProjectRequest.php
│   │   │   ├── Task/
│   │   │   │   ├── StoreTaskRequest.php
│   │   │   │   └── UpdateTaskRequest.php
│   │   │   └── Comment/
│   │   │       └── StoreCommentRequest.php
│   │   └── Resources/                    ← Correct Laravel 12 location
│   │       ├── UserResource.php
│   │       ├── ProjectResource.php
│   │       ├── TaskResource.php
│   │       └── CommentResource.php
│   ├── Jobs/
│   │   ├── SendTaskAssignedNotification.php
│   │   └── SendCommentNotification.php
│   ├── Listeners/
│   │   ├── NotifyTaskAssignee.php
│   │   └── NotifyCommentMentions.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── Task.php
│   │   └── Comment.php
│   ├── Notifications/
│   │   ├── TaskAssignedNotification.php
│   │   └── CommentPostedNotification.php
│   ├── Policies/
│   │   ├── ProjectPolicy.php
│   │   └── TaskPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   └── ProjectRepositoryInterface.php
│   │   └── ProjectRepository.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── ProjectService.php
│   │   ├── TaskService.php
│   │   └── CommentService.php
│   └── Support/
│       ├── ApiResponse.php
│       └── Enums/
│           ├── TaskStatus.php
│           └── TaskPriority.php
├── bootstrap/
│   └── app.php                           ← All middleware/routing/exceptions here
├── config/
│   ├── cors.php
│   ├── logging.php
│   └── taskflow.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docker/
│   ├── php/
│   │   └── Dockerfile                    ← Multi-stage build
│   └── nginx/
│       └── default.conf
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   ├── Feature/
│   │   ├── Auth/
│   │   ├── Projects/
│   │   ├── Tasks/
│   │   └── Comments/
│   └── Unit/
│       ├── Services/
│       ├── DTOs/
│       └── Filters/
├── .github/
│   └── workflows/
│       ├── ci.yml
│       └── deploy.yml
├── .dockerignore
├── docker-compose.yml
├── .env.example
└── README.md
```

## Design Rules

- One controller per resource. No business logic inside controllers.
- Resources live in `app/Http/Resources/` — not `app/Resources/`.
- DTOs are `readonly` classes — passed from controller to service, never mutated.
- Action classes are used for complex single-purpose operations that don't justify a full service.
- `Support/` holds stateless helpers and enums only — no side effects.
- Repositories are created only when the same Eloquent query appears in 2+ service methods.
- All `bootstrap/app.php` changes are the single source of truth for middleware, routing, and exceptions.

---

# 4. API Design Standards

## Base URL Convention

```
https://api.taskflow.com/api/v1/{resource}
```

## URL Naming Rules

| Method | Pattern | Description |
|--------|---------|-------------|
| GET | `/api/v1/projects` | List all |
| POST | `/api/v1/projects` | Create |
| GET | `/api/v1/projects/{id}` | Show single |
| PUT | `/api/v1/projects/{id}` | Full update |
| PATCH | `/api/v1/projects/{id}` | Partial update |
| DELETE | `/api/v1/projects/{id}` | Soft delete |
| GET | `/api/v1/projects/{id}/tasks` | Nested list |
| POST | `/api/v1/projects/{id}/tasks` | Create nested |
| GET | `/api/v1/tasks/{id}` | Shallow — avoids deep nesting |

**Rules:**
- Always plural nouns. Never verbs in routes.
- Shallow nested routes — max 2 levels, then break to root resource.
- Filtering via query parameters: `?status=open&priority=high&page=1&per_page=15`

## Standard Response Envelope

```json
{
  "success": true,
  "message": "Task created successfully.",
  "data": { },
  "meta": {
    "request_id": "req_01HV8XYZ",
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "v1"
  }
}
```

**Paginated:**
```json
{
  "success": true,
  "message": "Tasks retrieved.",
  "data": [],
  "meta": {
    "request_id": "req_01HV8XYZ",
    "timestamp": "2024-01-15T10:30:00Z",
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

**Error:**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": { "title": ["The title field is required."] },
  "meta": {
    "request_id": "req_01HV8XYZ",
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "v1"
  }
}
```

## HTTP Status Code Policy

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

---

# 5. Phase 1 — Project Setup

---

## Step 1.1 — Verify Laravel 12 Installation & Update `.env`

**What is being built:** Correct environment baseline for Redis-backed caching, queues, and sessions.

**The existing `composer.json` already has:**
```json
"laravel/framework": "^12.0",
"laravel/sanctum": "^4.0",
"php": "^8.2"
```

**Update `.env` — change these values:**
```ini
APP_NAME=TaskFlowAPI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Change from database to Redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

LOG_CHANNEL=stack
LOG_LEVEL=debug
```

**Critical:** The original `.env` has `CACHE_STORE=database` and `QUEUE_CONNECTION=database`. Both must be changed to `redis` before any caching or queue work begins.

**Checklist:**
- [ ] `php artisan config:clear` runs without error
- [ ] `php artisan tinker` then `Cache::store()->getStore()` shows `RedisStore`
- [ ] `.env` is in `.gitignore`
- [ ] `.env.example` committed with blanked secrets

**Git commit:**
```
chore(env): switch CACHE_STORE and QUEUE_CONNECTION from database to redis
```

---

## Step 1.2 — Configure `bootstrap/app.php` (Laravel 12 Central Config)

**What is being built:** The single `bootstrap/app.php` file that controls routing, middleware, and exception handling in Laravel 12. This replaces `Kernel.php` and `Handler.php` from older versions.

**File: `bootstrap/app.php`**
```php
<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetRequestId;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',   // ← THIS was missing; add it
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global API middleware — runs on every /api/* request
        $middleware->appendToGroup('api', [
            ForceJsonResponse::class,
            SetRequestId::class,
            SecurityHeaders::class,
        ]);

        // Alias for convenience in route files
        $middleware->alias([
            'request.id' => SetRequestId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // All API exceptions rendered as standard JSON envelope
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Validation failed.', 422, $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Unauthenticated.', 401);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Forbidden.', 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Resource not found.', 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return \App\Support\ApiResponse::error('Endpoint not found.', 404);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                report($e);
                $message = config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.';
                return \App\Support\ApiResponse::error($message, 500);
            }
        });
    })
    ->create();
```

> **Laravel 12 Note:** There is no `app/Exceptions/Handler.php`. All exception rendering is done inside `->withExceptions()` in `bootstrap/app.php`. If an old `Handler.php` exists in your project, delete it — it will be ignored.

**Checklist:**
- [ ] `GET /api/v1/nonexistent` returns 404 JSON (not HTML)
- [ ] Sending invalid data returns 422 JSON with `errors` key
- [ ] Unauthenticated request returns 401 JSON

**Git commit:**
```
feat(bootstrap): configure Laravel 12 routing, middleware, and exception handling in bootstrap/app.php
```

---

## Step 1.3 — CORS Configuration

**What is being built:** `config/cors.php` controlling which origins and headers are allowed. Laravel 12 handles CORS through this config file automatically.

**File: `config/cors.php`**
```php
<?php

return [
    'paths'                => ['api/*'],
    'allowed_methods'      => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins'      => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
    'allowed_origins_patterns' => [],
    'allowed_headers'      => ['Content-Type', 'Authorization', 'X-Request-ID', 'Accept'],
    'exposed_headers'      => ['X-Request-ID'],
    'max_age'              => 86400,
    'supports_credentials' => false,
];
```

**`.env` addition:**
```ini
CORS_ALLOWED_ORIGINS=http://localhost:3000,https://app.taskflow.com
```

**Checklist:**
- [ ] `OPTIONS /api/v1/projects` returns 200 with correct CORS headers
- [ ] `Access-Control-Allow-Origin` header present on all API responses

**Git commit:**
```
feat(cors): add config/cors.php with environment-based allowed origins
```

---

## Step 1.4 — ForceJsonResponse Middleware

**What is being built:** Middleware that forces `Accept: application/json` on all requests so Laravel always returns JSON errors, never HTML redirects.

**File: `app/Http/Middleware/ForceJsonResponse.php`**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
```

> If this file already exists, verify it is registered in `bootstrap/app.php` under `appendToGroup('api', [...])`.

**Git commit:**
```
feat(middleware): ensure ForceJsonResponse is registered in bootstrap/app.php API group
```

---

## Step 1.5 — SetRequestId Middleware

**What is being built:** Generates a unique UUID per request, injects it into the response header, and binds it in the container so every log line and every queued job can reference the originating request.

**File: `app/Http/Middleware/SetRequestId.php`**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        // Accept forwarded ID from upstream services (API gateway, load balancer)
        // or generate a fresh UUID for this request.
        $requestId = $request->header('X-Request-ID') ?: (string) Str::uuid();

        // Bind to the container so services, jobs, and log processors can access it.
        app()->instance('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
```

**Propagating `request_id` into queued Jobs:**
```php
// In any Job class, store it at dispatch time:
class SendTaskAssignedNotification implements ShouldQueue
{
    public function __construct(
        public readonly Task $task,
        public readonly User $assignee,
        public readonly string $requestId = '',  // ← propagated from HTTP context
    ) {}

    public function handle(): void
    {
        // Rebind so log lines inside the job carry the original request_id
        app()->instance('request_id', $this->requestId ?: 'queue');

        Log::info('Sending task assigned notification', [
            'task_id'    => $this->task->id,
            'assignee'   => $this->assignee->id,
        ]);

        $this->assignee->notify(new TaskAssignedNotification($this->task));
    }
}

// Dispatch from listener:
SendTaskAssignedNotification::dispatch(
    $task,
    $task->assignee,
    app()->has('request_id') ? app('request_id') : ''
)->onQueue('notifications');
```

**Checklist:**
- [ ] Every API response carries `X-Request-ID` header
- [ ] `app('request_id')` resolves in controllers, services, and jobs

**Git commit:**
```
feat(middleware): implement SetRequestId with container binding and job propagation
```

---

## Step 1.6 — SecurityHeaders Middleware

**What is being built:** Middleware that appends security headers to every API response. Demonstrates awareness of HTTP security hardening.

**File: `app/Http/Middleware/SecurityHeaders.php`**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Only add HSTS on HTTPS connections
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
```

**Git commit:**
```
feat(middleware): add SecurityHeaders middleware with HSTS, XSS, and frame protection
```

---

## Step 1.7 — ApiResponse Support Class

**What is being built:** Static helper used by all controllers to build the standard JSON envelope. Every response shape — success, error, paginated — flows through this single class.

**File: `app/Support/ApiResponse.php`**
```php
<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => self::meta(),
        ], $status);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'OK'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => array_merge(self::meta(), [
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ]),
        ], 200);
    }

    public static function error(
        string $message,
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'meta'    => self::meta(),
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    private static function meta(): array
    {
        return [
            'request_id' => app()->has('request_id') ? app('request_id') : null,
            'timestamp'  => now()->toIso8601String(),
            'version'    => 'v1',
        ];
    }
}
```

**Git commit:**
```
feat(support): implement standardized ApiResponse envelope helper
```

---

## Step 1.8 — Structured JSON Logging

**What is being built:** JSON-formatted log output with `request_id`, `user_id`, and `ip` on every line. Makes logs parseable by Datadog, ELK Stack, and Papertrail.

**File: `app/Logging/AddRequestContext.php`**
```php
<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;

class AddRequestContext
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            // JSON format — ingestible by Datadog / ELK
            $handler->setFormatter(new JsonFormatter());

            $handler->pushProcessor(function (array $record): array {
                $record['extra']['request_id'] = app()->has('request_id')
                    ? app('request_id')
                    : 'cli';

                $record['extra']['user_id'] = auth()->check()
                    ? auth()->id()
                    : null;

                $record['extra']['ip']  = request()->ip();
                $record['extra']['env'] = config('app.env');

                return $record;
            });
        }
    }
}
```

**Update `config/logging.php`:**
```php
'channels' => [
    'stack' => [
        'driver'   => 'stack',
        'channels' => ['daily'],
        'ignore_exceptions' => false,
    ],
    'daily' => [
        'driver' => 'daily',
        'path'   => storage_path('logs/laravel.log'),
        'level'  => env('LOG_LEVEL', 'debug'),
        'days'   => 14,
        'tap'    => [App\Logging\AddRequestContext::class],
    ],
],
```

**Checklist:**
- [ ] Log entries are valid JSON
- [ ] Every log line contains `request_id`, `user_id`, `ip`
- [ ] Log files rotate daily, kept 14 days

**Git commit:**
```
feat(logging): add structured JSON logging with request context for Datadog/ELK ingest
```

---

## Step 1.9 — Rate Limiting (Redis Sliding Window)

**What is being built:** Redis-backed rate limiting with separate limits per endpoint group and per HTTP verb. More sophisticated than simple `perMinute` limits.

**File: `app/Providers/AppServiceProvider.php`** (inside `boot()`):
```php
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository binding
        $this->app->bind(
            \App\Repositories\Contracts\ProjectRepositoryInterface::class,
            \App\Repositories\ProjectRepository::class,
        );

        // Policy bindings
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\Project::class,
            \App\Policies\ProjectPolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \App\Models\Task::class,
            \App\Policies\TaskPolicy::class
        );
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->registerEvents();
    }

    protected function configureRateLimiting(): void
    {
        // Standard API: 60 req/min per authenticated user or IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn () => \App\Support\ApiResponse::error(
                    'Too many requests. Slow down.', 429
                ));
        });

        // Auth endpoints: strict 10 req/min per IP (brute-force protection)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(fn () => \App\Support\ApiResponse::error(
                    'Too many authentication attempts. Try again in a minute.', 429
                ));
        });

        // Write operations: 30 req/min per user (POST, PUT, PATCH, DELETE)
        RateLimiter::for('writes', function (Request $request) {
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                return Limit::perMinute(30)
                    ->by($request->user()?->id ?: $request->ip())
                    ->response(fn () => \App\Support\ApiResponse::error(
                        'Write rate limit exceeded.', 429
                    ));
            }
            return Limit::none();
        });
    }

    protected function registerEvents(): void
    {
        // Laravel 12 event registration in AppServiceProvider
        // (replaces EventServiceProvider in older versions)
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\TaskCreated::class,
            \App\Listeners\NotifyTaskAssignee::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\TaskStatusChanged::class,
            \App\Listeners\NotifyTaskAssignee::class,
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\CommentPosted::class,
            \App\Listeners\NotifyCommentMentions::class,
        );
    }
}
```

**Git commit:**
```
feat(ratelimit): configure Redis-backed rate limiting with per-verb and per-group limits
```

---

## Step 1.10 — API Route Structure

**What is being built:** Versioned route file at `/api/v1/`. This is now loaded via `bootstrap/app.php` (Step 1.2).

**File: `routes/api.php`**
```php
<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

// Health endpoint (no auth, no rate limit — used by load balancers)
Route::get('/health', function () {
    $checks = [];

    try {
        \DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Exception) {
        $checks['database'] = 'unavailable';
    }

    try {
        \Illuminate\Support\Facades\Redis::ping();
        $checks['redis'] = 'ok';
    } catch (\Exception) {
        $checks['redis'] = 'unavailable';
    }

    try {
        \Illuminate\Support\Facades\Cache::store()->put('health_check', '1', 5);
        $checks['cache'] = 'ok';
    } catch (\Exception) {
        $checks['cache'] = 'unavailable';
    }

    try {
        \Illuminate\Support\Facades\Queue::size();
        $checks['queue'] = 'ok';
    } catch (\Exception) {
        $checks['queue'] = 'unavailable';
    }

    $healthy = ! in_array('unavailable', $checks);

    return \App\Support\ApiResponse::success([
        'status'  => $healthy ? 'healthy' : 'degraded',
        'checks'  => $checks,
        'uptime'  => round(microtime(true) - LARAVEL_START, 3) . 's',
        'version' => config('app.version', '1.0.0'),
    ], 'Health check complete', $healthy ? 200 : 503);
})->name('health');

Route::prefix('v1')->name('v1.')->group(function () {

    // ── Auth (rate limited to 10/min per IP) ────────────────────────────
    Route::prefix('auth')->name('auth.')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login',    [AuthController::class, 'login'])->name('login');
    });

    // ── Protected routes ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me',      [AuthController::class, 'me'])->name('auth.me');

        // Projects
        Route::apiResource('projects', ProjectController::class)
            ->middleware('throttle:writes');

        // Tasks — shallow nested (GET /projects/{project}/tasks, POST /projects/{project}/tasks)
        // then individual task ops at /tasks/{task} (shallow avoids /projects/{project}/tasks/{task})
        Route::apiResource('projects.tasks', TaskController::class)
            ->shallow()
            ->middleware('throttle:writes');

        // Comments — shallow nested under tasks
        Route::apiResource('tasks.comments', CommentController::class)
            ->only(['index', 'store', 'destroy'])
            ->shallow()
            ->middleware('throttle:writes');

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/',             [NotificationController::class, 'index'])->name('index');
            Route::patch('/{id}/read',  [NotificationController::class, 'markRead'])->name('read');
            Route::patch('/read-all',   [NotificationController::class, 'markAllRead'])->name('read-all');
        });
    });
});
```

**Checklist:**
- [ ] `php artisan route:list` shows all routes under `/api/v1/`
- [ ] `GET /api/health` returns 200 with 4 service checks
- [ ] Unauthenticated request to protected route returns 401 JSON

**Git commit:**
```
feat(routes): establish versioned API route structure with health check and rate limit groups
```

---

## Step 1.11 — OpenAPI Documentation with Scramble

**What is being built:** Zero-config OpenAPI 3.1 documentation using `dedoc/scramble` — a Laravel 12 compatible package that generates docs automatically from your route/request/resource code.

**Install:**
```bash
composer require dedoc/scramble
php artisan vendor:publish --provider="Dedoc\Scramble\ScrambleServiceProvider" --tag="scramble-config"
```

**File: `config/scramble.php`** (key settings):
```php
return [
    'api_path' => 'api',
    'api_domain' => null,

    'info' => [
        'version' => env('APP_VERSION', '1.0.0'),
        'description' => 'TaskFlow API — Production-grade SaaS task management backend.',
    ],

    'middleware' => [
        'web',
        // Add auth middleware here if you want protected docs
    ],

    'extensions' => [],
];
```

**Access docs at:** `http://localhost:8000/docs`  
**Access OpenAPI JSON at:** `http://localhost:8000/docs/api.json`

Add `@response` and `@responseParam` docblocks to controllers for richer documentation:
```php
/**
 * Create a new project.
 *
 * @response 201 scenario="Created" {"success": true, "message": "Project created."}
 * @response 422 scenario="Validation error" {"success": false, "errors": {}}
 */
public function store(StoreProjectRequest $request): JsonResponse
```

**Checklist:**
- [ ] `GET /docs` renders Swagger UI with all endpoints
- [ ] `GET /docs/api.json` returns valid OpenAPI 3.1 JSON
- [ ] Auth endpoints show 401 responses

**Git commit:**
```
feat(docs): integrate dedoc/scramble for zero-config OpenAPI 3.1 documentation
```

---

## Step 1.12 — Docker Setup (Multi-Stage)

**What is being built:** Production-grade multi-stage Docker build that separates build dependencies from the final runtime image. Reduces image size and attack surface.

**File: `docker/php/Dockerfile`**
```dockerfile
# Stage 1: Composer dependency installer
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

# Stage 2: Final runtime image
FROM php:8.2-fpm-alpine

# Install runtime extensions only
RUN apk add --no-cache \
    libpng-dev libzip-dev libxml2-dev oniguruma-dev curl \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath pcntl

RUN pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www

# Copy vendor from build stage (not from host)
COPY --from=vendor /app/vendor ./vendor
COPY . .

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
```

**File: `.dockerignore`**
```
.git
.github
node_modules
vendor
storage/logs/*
storage/framework/cache/*
tests/
.env
.env.*
*.md
docker/
```

**File: `docker-compose.yml`**
```yaml
version: '3.9'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
      target: vendor      # use build stage for local dev (includes dev deps)
    container_name: taskflow_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - .:/var/www
      - vendor_cache:/var/www/vendor
    networks:
      - taskflow
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy
    environment:
      - APP_ENV=local

  nginx:
    image: nginx:1.25-alpine
    container_name: taskflow_nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - .:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - taskflow
    depends_on:
      - app
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/api/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  mysql:
    image: mysql:8.0
    container_name: taskflow_mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: taskflow
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_USER: taskflow
      MYSQL_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - taskflow
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    container_name: taskflow_redis
    restart: unless-stopped
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    ports:
      - "6379:6379"
    networks:
      - taskflow
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 3

  queue:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: taskflow_queue
    restart: unless-stopped
    working_dir: /var/www
    command: php artisan queue:work redis --sleep=3 --tries=3 --queue=notifications,default --timeout=90
    volumes:
      - .:/var/www
    networks:
      - taskflow
    depends_on:
      - redis
      - mysql

volumes:
  mysql_data:
  redis_data:
  vendor_cache:

networks:
  taskflow:
    driver: bridge
```

**Git commit:**
```
chore(docker): multi-stage Dockerfile, .dockerignore, healthchecks, persistent volumes
```

---

# 6. Phase 2 — Authentication

---

## Step 2.1 — Sanctum Configuration & User Model

**What is being built:** Token-based API authentication. The User model must have `HasApiTokens` trait — this is missing from the current state.

**Update `app/Models/User.php`:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
```

**Commands:**
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

**Checklist:**
- [ ] `personal_access_tokens` table exists
- [ ] `HasApiTokens` on User model
- [ ] `php artisan tinker` → `User::first()->createToken('test')` works

**Git commit:**
```
feat(auth): add HasApiTokens to User model, publish and run Sanctum migration
```

---

## Step 2.2 — Auth DTOs

**What is being built:** Typed readonly DTOs that carry validated data from the Form Request to the Service. Eliminates raw array passing and makes service method signatures self-documenting.

**File: `app/DTOs/Auth/RegisterDTO.php`**
```php
<?php

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\RegisterRequest;

final readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name:     $request->validated('name'),
            email:    $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
```

**Git commit:**
```
feat(dto): add RegisterDTO as typed data carrier from request to service
```

---

## Step 2.3 — Auth Form Requests

**File: `app/Http/Requests/Auth/RegisterRequest.php`**
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:100'],
            'email'    => ['required', 'email:rfc,dns', 'unique:users,email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

**File: `app/Http/Requests/Auth/LoginRequest.php`**
```php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

**Git commit:**
```
feat(auth): add RegisterRequest and LoginRequest form validation classes
```

---

## Step 2.4 — AuthService

**File: `app/Services/AuthService.php`**
```php
<?php

namespace App\Services;

use App\DTOs\Auth\RegisterDTO;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'password' => $dto->password,  // cast 'hashed' handles bcrypt
        ]);

        $token = $user->createToken('api-token', ['*'])->plainTextToken;

        return compact('user', 'token');
    }

    public function login(LoginRequest $request): array
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw new AuthenticationException('Invalid credentials.');
        }

        // Single-session: revoke all previous tokens on login
        $user->tokens()->delete();

        $token = $user->createToken('api-token', ['*'])->plainTextToken;

        return compact('user', 'token');
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
```

**Git commit:**
```
feat(auth): implement AuthService using RegisterDTO for typed input passing
```

---

## Step 2.5 — AuthController & UserResource

**File: `app/Http/Resources/UserResource.php`**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

**File: `app/Http/Controllers/Api/V1/AuthController.php`**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Auth\RegisterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(RegisterDTO::fromRequest($request));

        return ApiResponse::success([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Account created successfully.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request);

        return ApiResponse::success([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new UserResource($request->user()),
            'Authenticated user retrieved.'
        );
    }
}
```

**Checklist:**
- [ ] `POST /api/v1/auth/register` returns 201 with user + token
- [ ] `POST /api/v1/auth/login` returns 200 with token
- [ ] `POST /api/v1/auth/logout` invalidates token, subsequent requests return 401
- [ ] `GET /api/v1/auth/me` returns user without password

**Git commit:**
```
feat(auth): implement AuthController with DTO pattern and UserResource
```

---

# 7. Phase 3 — Projects Module

---

## Step 3.1 — Project Migration & Model

**Migration:**
```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
    $table->string('name', 150);
    $table->text('description')->nullable();
    $table->enum('status', ['active', 'archived'])->default('active');
    $table->timestamps();
    $table->softDeletes();

    // Composite indexes for query patterns
    $table->index(['owner_id', 'status']);
    $table->index(['owner_id', 'deleted_at']);   // soft-delete scoped owner queries
    $table->index('deleted_at');
});

Schema::create('project_user', function (Blueprint $table) {
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('role', ['member', 'admin'])->default('member');
    $table->timestamps();

    $table->primary(['project_id', 'user_id']);
    $table->index(['user_id', 'project_id']);   // for member lookup
});
```

**File: `app/Models/Project.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['owner_id', 'name', 'description', 'status'];

    protected function casts(): array
    {
        return ['deleted_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function hasAccess(User $user): bool
    {
        return $this->owner_id === $user->id
            || $this->members()->where('user_id', $user->id)->exists();
    }
}
```

**Git commit:**
```
feat(projects): add projects and project_user migrations with composite soft-delete indexes
```

---

## Step 3.2 — Project DTOs

**File: `app/DTOs/Project/CreateProjectDTO.php`**
```php
<?php

namespace App\DTOs\Project;

use App\Http\Requests\Project\StoreProjectRequest;

final readonly class CreateProjectDTO
{
    public function __construct(
        public string  $name,
        public ?string $description,
    ) {}

    public static function fromRequest(StoreProjectRequest $request): self
    {
        return new self(
            name:        $request->validated('name'),
            description: $request->validated('description'),
        );
    }
}
```

**File: `app/DTOs/Project/UpdateProjectDTO.php`**
```php
<?php

namespace App\DTOs\Project;

use App\Http\Requests\Project\UpdateProjectRequest;

final readonly class UpdateProjectDTO
{
    public function __construct(
        public ?string $name,
        public ?string $description,
        public ?string $status,
    ) {}

    public static function fromRequest(UpdateProjectRequest $request): self
    {
        return new self(
            name:        $request->validated('name'),
            description: $request->validated('description'),
            status:      $request->validated('status'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name'        => $this->name,
            'description' => $this->description,
            'status'      => $this->status,
        ], fn ($v) => $v !== null);
    }
}
```

**Git commit:**
```
feat(dto): add CreateProjectDTO and UpdateProjectDTO
```

---

## Step 3.3 — Project Policy

**File: `app/Policies/ProjectPolicy.php`**
```php
<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $project->hasAccess($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->owner_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->owner_id;
    }
}
```

**Git commit:**
```
feat(projects): implement ProjectPolicy with owner/member authorization rules
```

---

## Step 3.4 — ProjectRepository (with Dual Task Counts)

**File: `app/Repositories/Contracts/ProjectRepositoryInterface.php`**
```php
<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator;
}
```

**File: `app/Repositories/ProjectRepository.php`**
```php
<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Project::query()
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id));
            })
            ->with(['owner:id,name,email'])
            // Dual count — demonstrates active reasoning about data states.
            // Eloquent's withCount() is already soft-delete aware (deleted tasks excluded).
            // We split into named counts to expose both total and active task states.
            ->withCount([
                'tasks as total_tasks',
                'tasks as active_tasks' => fn ($q) => $q->whereNotIn('status', ['done', 'cancelled']),
            ])
            ->latest()
            ->paginate($perPage);
    }
}
```

**Git commit:**
```
feat(projects): add ProjectRepository with user-scoped query and dual soft-delete-aware task counts
```

---

## Step 3.5 — ProjectService

**File: `app/Services/ProjectService.php`**
```php
<?php

namespace App\Services;

use App\DTOs\Project\CreateProjectDTO;
use App\DTOs\Project\UpdateProjectDTO;
use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $page     = request('page', 1);
        $cacheKey = "user:{$user->id}:projects:page:{$page}";

        return Cache::tags(["user:{$user->id}:projects"])
            ->remember($cacheKey, 300, fn () =>
                $this->projectRepository->paginateForUser($user, $perPage)
            );
    }

    public function create(User $user, CreateProjectDTO $dto): Project
    {
        $project = Project::create([
            'owner_id'    => $user->id,
            'name'        => $dto->name,
            'description' => $dto->description,
            'status'      => 'active',
        ]);

        $this->clearUserCache($user->id);

        return $project->load('owner');
    }

    public function update(Project $project, UpdateProjectDTO $dto): Project
    {
        $project->update($dto->toArray());

        $this->clearUserCache($project->owner_id);

        return $project->fresh(['owner']);
    }

    public function delete(Project $project): void
    {
        $ownerId = $project->owner_id;
        $project->delete();

        $this->clearUserCache($ownerId);
    }

    private function clearUserCache(int $userId): void
    {
        Cache::tags(["user:{$userId}:projects"])->flush();
    }
}
```

**Git commit:**
```
feat(projects): implement ProjectService with DTO input, Redis cache, and tag-based invalidation
```

---

## Step 3.6 — ProjectController & ProjectResource

**File: `app/Http/Resources/ProjectResource.php`**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'description'  => $this->description,
            'status'       => $this->status,
            'total_tasks'  => $this->total_tasks ?? 0,
            'active_tasks' => $this->active_tasks ?? 0,
            'owner'        => new UserResource($this->whenLoaded('owner')),
            'created_at'   => $this->created_at->toIso8601String(),
            'updated_at'   => $this->updated_at->toIso8601String(),
        ];
    }
}
```

**File: `app/Http/Controllers/Api/V1/ProjectController.php`**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Project\CreateProjectDTO;
use App\DTOs\Project\UpdateProjectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->listForUser($request->user());

        return ApiResponse::paginated(
            $projects->through(fn ($p) => new ProjectResource($p)),
            'Projects retrieved.'
        );
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create(
            $request->user(),
            CreateProjectDTO::fromRequest($request)
        );

        return ApiResponse::success(new ProjectResource($project), 'Project created.', 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return ApiResponse::success(
            new ProjectResource($project->load('owner')->loadCount([
                'tasks as total_tasks',
                'tasks as active_tasks' => fn ($q) => $q->whereNotIn('status', ['done', 'cancelled']),
            ])),
            'Project retrieved.'
        );
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project = $this->projectService->update(
            $project,
            UpdateProjectDTO::fromRequest($request)
        );

        return ApiResponse::success(new ProjectResource($project), 'Project updated.');
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->delete($project);

        return ApiResponse::success(null, 'Project deleted.', 204);
    }
}
```

**Checklist:**
- [ ] `GET /api/v1/projects` returns paginated list scoped to user
- [ ] `POST /api/v1/projects` creates and returns 201
- [ ] `DELETE /api/v1/projects/{id}` by non-owner returns 403
- [ ] Deleted project is soft-deleted (row still in DB with `deleted_at`)

**Git commit:**
```
feat(projects): implement ProjectController with DTO pattern and ProjectResource
```

---

# 8. Phase 4 — Tasks Module

---

## Step 4.1 — Task Enums

**File: `app/Support/Enums/TaskStatus.php`**
```php
<?php

namespace App\Support\Enums;

enum TaskStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case InReview   = 'in_review';
    case Done       = 'done';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Open       => 'Open',
            self::InProgress => 'In Progress',
            self::InReview   => 'In Review',
            self::Done       => 'Done',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Done, self::Cancelled]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

**File: `app/Support/Enums/TaskPriority.php`**
```php
<?php

namespace App\Support\Enums;

enum TaskPriority: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';
    case Urgent = 'urgent';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

**Git commit:**
```
feat(tasks): add TaskStatus and TaskPriority PHP 8.1 backed enums with helper methods
```

---

## Step 4.2 — Task Migration & Model

**Migration:**
```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->string('title', 200);
    $table->text('description')->nullable();
    $table->string('status', 20)->default('open');
    $table->string('priority', 20)->default('medium');
    $table->date('due_date')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Composite indexes for the filter patterns used in TaskQueryFilter
    $table->index(['project_id', 'status']);
    $table->index(['project_id', 'priority']);
    $table->index(['project_id', 'deleted_at']);   // soft-delete scoped project queries
    $table->index(['assigned_to', 'status']);
    $table->index('due_date');
});
```

**File: `app/Models/Task.php`**
```php
<?php

namespace App\Models;

use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'created_by', 'assigned_to',
        'title', 'description', 'status', 'priority', 'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status'     => TaskStatus::class,
            'priority'   => TaskPriority::class,
            'due_date'   => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
```

**Git commit:**
```
feat(tasks): add tasks migration with composite indexes, Task model with enum casts
```

---

## Step 4.3 — TaskQueryFilter (Filter Pattern)

**What is being built:** A dedicated composable filter class that isolates all task query logic. Instead of chaining `when()` calls inline in the service, filters are encapsulated, testable, and extendable.

**File: `app/Filters/TaskQueryFilter.php`**
```php
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
            // Validate enum value before filtering
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
        // Always eager load — N+1 prevention
        $this->query->with(['creator:id,name', 'assignee:id,name,email'])
                    ->withCount('comments');
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
```

**Git commit:**
```
feat(filters): add TaskQueryFilter with composable filter chain pattern
```

---

## Step 4.4 — Task DTOs

**File: `app/DTOs/Task/CreateTaskDTO.php`**
```php
<?php

namespace App\DTOs\Task;

use App\Http\Requests\Task\StoreTaskRequest;

final readonly class CreateTaskDTO
{
    public function __construct(
        public string  $title,
        public ?string $description,
        public ?int    $assignedTo,
        public string  $priority,
        public ?string $dueDate,
    ) {}

    public static function fromRequest(StoreTaskRequest $request): self
    {
        return new self(
            title:       $request->validated('title'),
            description: $request->validated('description'),
            assignedTo:  $request->validated('assigned_to'),
            priority:    $request->validated('priority', 'medium'),
            dueDate:     $request->validated('due_date'),
        );
    }
}
```

**Git commit:**
```
feat(dto): add CreateTaskDTO and UpdateTaskDTO
```

---

## Step 4.5 — Task Events

**What is being built:** Domain events designed to be forward-compatible with event sourcing. Each event is a plain, readonly, serializable value object. If you later adopt an event store (e.g., `spatie/laravel-event-sourcing`), these events require no structural changes.

**File: `app/Events/TaskCreated.php`**
```php
<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event Sourcing Note:
 * This event is a plain value object — no behavior, only data.
 * It records WHAT happened (a task was created) not WHY or HOW.
 * Compatible with spatie/laravel-event-sourcing if adopted later.
 */
final class TaskCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly string $requestId = '',
    ) {}
}
```

**File: `app/Events/TaskStatusChanged.php`**
```php
<?php

namespace App\Events;

use App\Models\Task;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TaskStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task       $task,
        public readonly TaskStatus $previousStatus,
        public readonly TaskStatus $newStatus,
        public readonly string     $requestId = '',
    ) {}
}
```

**File: `app/Events/CommentPosted.php`**
```php
<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CommentPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Comment $comment,
        public readonly string  $requestId = '',
    ) {}
}
```

**Git commit:**
```
feat(events): add TaskCreated, TaskStatusChanged, CommentPosted — event-sourcing compatible value objects
```

---

## Step 4.6 — TaskService (Russian Doll Caching)

**File: `app/Services/TaskService.php`**
```php
<?php

namespace App\Services;

use App\DTOs\Task\CreateTaskDTO;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Filters\TaskQueryFilter;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class TaskService
{
    public function listForProject(Project $project, array $filters = []): LengthAwarePaginator
    {
        // Russian Doll Cache: embed project's updated_at timestamp as version.
        // $project->touch() on any task write changes the timestamp, which changes
        // the cache key — all prior filter-variant caches become unreachable and
        // expire via TTL. No manual flush, no tag management needed.
        $version    = $project->updated_at->timestamp;
        $filterHash = md5(serialize($filters) . request('page', 1));
        $cacheKey   = "project:{$project->id}:v{$version}:tasks:{$filterHash}";

        return Cache::remember($cacheKey, 300, function () use ($project, $filters) {
            return TaskQueryFilter::apply(array_merge($filters, ['project_id' => $project->id]))
                ->paginate(15);
        });
    }

    public function create(Project $project, User $creator, CreateTaskDTO $dto): Task
    {
        $task = Task::create([
            'project_id'  => $project->id,
            'created_by'  => $creator->id,
            'assigned_to' => $dto->assignedTo,
            'title'       => $dto->title,
            'description' => $dto->description,
            'status'      => TaskStatus::Open,
            'priority'    => $dto->priority,
            'due_date'    => $dto->dueDate,
        ]);

        // Touch project to bust ALL cached task queries for this project.
        $project->touch();

        event(new TaskCreated(
            task:      $task->load(['assignee', 'project']),
            requestId: app()->has('request_id') ? app('request_id') : '',
        ));

        return $task;
    }

    public function update(Task $task, array $data): Task
    {
        $previousStatus = $task->status;

        $task->update(array_filter($data, fn ($v) => $v !== null));

        $task->project->touch();

        if (isset($data['status']) && $data['status'] !== $previousStatus->value) {
            event(new TaskStatusChanged(
                task:           $task->fresh(),
                previousStatus: $previousStatus,
                newStatus:      TaskStatus::from($data['status']),
                requestId:      app()->has('request_id') ? app('request_id') : '',
            ));
        }

        return $task->fresh(['assignee', 'creator']);
    }

    public function delete(Task $task): void
    {
        $task->project->touch();
        $task->delete();
    }
}
```

**Git commit:**
```
feat(tasks): implement TaskService with TaskQueryFilter, DTO input, and Russian Doll caching
```

---

## Step 4.7 — Action Classes (Alternative Pattern)

**What is being built:** Dedicated single-purpose Action classes for complex operations. Useful when an operation is complex enough to deserve its own class but doesn't belong in a general-purpose service.

**File: `app/Actions/Task/AssignTaskAction.php`**
```php
<?php

namespace App\Actions\Task;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Action Pattern Note:
 * Actions are used when a single operation is complex enough to need
 * its own class but doesn't justify extending a full service.
 * They have a single public method: handle() or __invoke().
 * Unlike Services (orchestrators), Actions do ONE thing.
 */
class AssignTaskAction
{
    public function handle(Task $task, User $assignee): Task
    {
        $previousAssigneeId = $task->assigned_to;

        $task->update(['assigned_to' => $assignee->id]);

        Log::info('Task assigned', [
            'task_id'              => $task->id,
            'new_assignee_id'      => $assignee->id,
            'previous_assignee_id' => $previousAssigneeId,
        ]);

        event(new TaskCreated($task->fresh(['assignee', 'project'])));

        return $task->fresh('assignee');
    }
}
```

**File: `app/Actions/Project/ArchiveProjectAction.php`**
```php
<?php

namespace App\Actions\Project;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;

class ArchiveProjectAction
{
    public function handle(Project $project): Project
    {
        $project->update(['status' => 'archived']);

        // Invalidate cached project list for this owner
        Cache::tags(["user:{$project->owner_id}:projects"])->flush();

        return $project->fresh();
    }
}
```

**Git commit:**
```
feat(actions): add AssignTaskAction and ArchiveProjectAction as single-purpose action classes
```

---

## Step 4.8 — TaskController & TaskResource

**File: `app/Http/Resources/TaskResource.php`**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'project_id'     => $this->project_id,
            'title'          => $this->title,
            'description'    => $this->description,
            'status'         => [
                'value'      => $this->status->value,
                'label'      => $this->status->label(),
                'is_terminal'=> $this->status->isTerminal(),
            ],
            'priority'       => $this->priority->value,
            'due_date'       => $this->due_date?->toDateString(),
            'is_overdue'     => $this->due_date
                && ! $this->status->isTerminal()
                && $this->due_date->isPast(),
            'comments_count' => $this->comments_count ?? 0,
            'creator'        => new UserResource($this->whenLoaded('creator')),
            'assignee'       => new UserResource($this->whenLoaded('assignee')),
            'created_at'     => $this->created_at->toIso8601String(),
            'updated_at'     => $this->updated_at->toIso8601String(),
        ];
    }
}
```

**File: `app/Http/Controllers/Api/V1/TaskController.php`**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Task\CreateTaskDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $tasks = $this->taskService->listForProject(
            $project,
            $request->only(['status', 'priority', 'assigned_to', 'overdue', 'sort_by', 'sort_dir'])
        );

        return ApiResponse::paginated(
            $tasks->through(fn ($t) => new TaskResource($t)),
            'Tasks retrieved.'
        );
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $task = $this->taskService->create(
            $project,
            $request->user(),
            CreateTaskDTO::fromRequest($request)
        );

        return ApiResponse::success(new TaskResource($task), 'Task created.', 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task->project);

        return ApiResponse::success(
            new TaskResource($task->load(['creator', 'assignee'])->loadCount('comments')),
            'Task retrieved.'
        );
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->taskService->update($task, $request->validated());

        return ApiResponse::success(new TaskResource($task), 'Task updated.');
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->delete($task);

        return ApiResponse::success(null, 'Task deleted.', 204);
    }
}
```

**Checklist:**
- [ ] `GET /api/v1/projects/{id}/tasks?status=open&priority=high` filters correctly
- [ ] `GET /api/v1/tasks/{id}` works (shallow route)
- [ ] Task Resource shows `is_overdue` correctly
- [ ] Cache key changes after task creation (touch works)

**Git commit:**
```
feat(tasks): implement TaskController with DTO pattern, filter params, and TaskResource
```

---

# 9. Phase 5 — Comments Module

---

## Step 5.1 — Comment Migration & Model

**Migration:**
```php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('task_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->text('body');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['task_id', 'created_at']);
    $table->index(['task_id', 'deleted_at']);
});
```

**File: `app/Models/Comment.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['task_id', 'user_id', 'body'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

**Git commit:**
```
feat(comments): add comments migration with soft-delete index, Comment model
```

---

## Step 5.2 — CommentService with DB Transaction

**File: `app/Services/CommentService.php`**
```php
<?php

namespace App\Services;

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentService
{
    public function listForTask(Task $task): LengthAwarePaginator
    {
        return Comment::query()
            ->where('task_id', $task->id)
            ->with(['author:id,name,email'])
            ->oldest()
            ->paginate(25);
    }

    public function create(Task $task, User $author, string $body): Comment
    {
        // Wrap in DB transaction: if anything rolls back after Comment::create()
        // the queued notification job will NOT fire (after_commit semantics).
        // This prevents workers from looking up a Comment that no longer exists.
        // Ensure queue connection has 'after_commit' => true in config/queue.php.
        return DB::transaction(function () use ($task, $author, $body) {
            $comment = Comment::create([
                'task_id' => $task->id,
                'user_id' => $author->id,
                'body'    => $body,
            ]);

            Log::info('Comment created', [
                'comment_id' => $comment->id,
                'task_id'    => $task->id,
                'author_id'  => $author->id,
            ]);

            // Event registered inside transaction; listener's queued Job
            // will only dispatch after successful DB commit.
            event(new CommentPosted(
                comment:   $comment->load(['author', 'task.project']),
                requestId: app()->has('request_id') ? app('request_id') : '',
            ));

            return $comment;
        });
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
```

**Git commit:**
```
feat(comments): implement CommentService with DB::transaction and after-commit event dispatch
```

---

# 10. Phase 6 — Notifications System

---

## Step 6.1 — Notifications Migration

```bash
php artisan make:migration create_notifications_table
```

```php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('type');
    $table->morphs('notifiable');
    $table->json('data');
    $table->timestamp('read_at')->nullable();
    $table->timestamps();

    // Query: fetch unread notifications for a user
    $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
});
```

**Git commit:**
```
feat(notifications): add database notifications table with composite read_at index
```

---

## Step 6.2 — Notification + Job + Listener

**File: `app/Notifications/TaskAssignedNotification.php`**
```php
<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    public function __construct(
        private readonly Task   $task,
        private readonly string $requestId = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'task_assigned',
            'task_id'     => $this->task->id,
            'task_title'  => $this->task->title,
            'project_id'  => $this->task->project_id,
            'message'     => "You have been assigned to: {$this->task->title}",
            'request_id'  => $this->requestId,  // traceable back to originating request
        ];
    }
}
```

**File: `app/Jobs/SendTaskAssignedNotification.php`**
```php
<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTaskAssignedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Job-level retry configuration
    public int $tries   = 3;
    public int $backoff = 60;

    // Runs after DB commit (configured at connection level in config/queue.php)
    public bool $afterCommit = true;

    public function __construct(
        public readonly Task   $task,
        public readonly User   $assignee,
        public readonly string $requestId = '',
    ) {}

    public function handle(): void
    {
        // Rebind request_id so job logs are traceable to the original HTTP request
        app()->instance('request_id', $this->requestId ?: 'queue-job');

        Log::info('Dispatching TaskAssignedNotification', [
            'task_id'  => $this->task->id,
            'assignee' => $this->assignee->id,
        ]);

        $this->assignee->notify(
            new TaskAssignedNotification($this->task, $this->requestId)
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendTaskAssignedNotification failed', [
            'task_id'    => $this->task->id,
            'assignee'   => $this->assignee->id,
            'error'      => $e->getMessage(),
            'request_id' => $this->requestId,
        ]);
    }
}
```

**File: `app/Listeners/NotifyTaskAssignee.php`**
```php
<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Jobs\SendTaskAssignedNotification;

class NotifyTaskAssignee
{
    public function handle(TaskCreated $event): void
    {
        if ($event->task->assignee === null) {
            return;
        }

        // Listener stays thin — all async work delegated to Job.
        // request_id propagated for distributed tracing in async context.
        SendTaskAssignedNotification::dispatch(
            task:      $event->task,
            assignee:  $event->task->assignee,
            requestId: $event->requestId,
        )->onQueue('notifications');
    }
}
```

**Git commit:**
```
feat(notifications): implement queued notification job with request_id propagation and after-commit dispatch
```

---

## Step 6.3 — NotificationController

**File: `app/Http/Controllers/Api/V1/NotificationController.php`**
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return ApiResponse::paginated($notifications, 'Notifications retrieved.');
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return ApiResponse::success(null, 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::success(null, 'All notifications marked as read.');
    }
}
```

**Git commit:**
```
feat(notifications): implement NotificationController with read/read-all endpoints
```

---

# 11. Phase 7 — Testing Strategy

---

## Step 7.1 — PHPUnit Configuration

**File: `phpunit.xml`**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <report>
            <html outputDirectory="coverage-report"/>
        </report>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
    </php>
</phpunit>
```

**Base test case — `tests/Feature/FeatureTestCase.php`:**
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function actingAsUser(?User $user = null): User
    {
        $user ??= User::factory()->create();
        $this->actingAs($user, 'sanctum');
        return $user;
    }
}
```

**Git commit:**
```
test(config): configure PHPUnit with in-memory SQLite, array cache, and sync queue
```

---

## Step 7.2 — Authentication Feature Tests

**File: `tests/Feature/Auth/AuthenticationTest.php`**
```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Feature\FeatureTestCase;

class AuthenticationTest extends FeatureTestCase
{
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Umar Khan',
            'email'                 => 'umar@example.com',
            'password'              => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['user' => ['id', 'name', 'email'], 'token'],
                'meta' => ['request_id', 'timestamp', 'version'],
            ])
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['email' => 'umar@example.com']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'umar@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Umar Khan',
            'email'                 => 'umar@example.com',
            'password'              => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
        ])->assertStatus(422)
          ->assertJsonPath('success', false)
          ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('SecurePass123')]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'SecurePass123',
        ])->assertOk()
          ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');
    }

    public function test_me_endpoint_returns_current_user(): void
    {
        $user = $this->actingAsUser();

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }
}
```

**Git commit:**
```
test(auth): add feature tests for register, login, logout, and me endpoints
```

---

## Step 7.3 — Project Feature Tests (with Policy)

**File: `tests/Feature/Projects/ProjectCrudTest.php`**
```php
<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class ProjectCrudTest extends FeatureTestCase
{
    public function test_authenticated_user_can_create_project(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/v1/projects', [
            'name'        => 'Alpha Project',
            'description' => 'First project',
        ])->assertStatus(201)
          ->assertJsonPath('data.name', 'Alpha Project')
          ->assertJsonPath('data.total_tasks', 0)
          ->assertJsonPath('data.active_tasks', 0);

        $this->assertDatabaseHas('projects', ['name' => 'Alpha Project']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->postJson('/api/v1/projects', ['name' => 'Test'])
            ->assertStatus(401);
    }

    public function test_user_only_sees_their_own_projects(): void
    {
        $owner  = $this->actingAsUser();
        $other  = User::factory()->create();
        $myProj = Project::factory()->create(['owner_id' => $owner->id]);
        $theirP = Project::factory()->create(['owner_id' => $other->id]);

        $ids = collect($this->getJson('/api/v1/projects')->json('data'))->pluck('id');

        $this->assertContains($myProj->id, $ids);
        $this->assertNotContains($theirP->id, $ids);
    }

    public function test_non_owner_cannot_delete_project(): void
    {
        $owner   = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->actingAsUser(); // different user

        $this->deleteJson("/api/v1/projects/{$project->id}")
            ->assertStatus(403);
    }

    public function test_owner_can_soft_delete_project(): void
    {
        $owner   = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->deleteJson("/api/v1/projects/{$project->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
```

**Git commit:**
```
test(projects): add feature tests for CRUD, policy enforcement, and soft delete
```

---

## Step 7.4 — Advanced Task Tests (Queue, Cache, Policy)

**File: `tests/Feature/Tasks/TaskTest.php`**
```php
<?php

namespace Tests\Feature\Tasks;

use App\Events\TaskCreated;
use App\Jobs\SendTaskAssignedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\FeatureTestCase;

class TaskTest extends FeatureTestCase
{
    public function test_creating_task_dispatches_task_created_event(): void
    {
        Event::fake([TaskCreated::class]);

        $owner   = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title'    => 'Fix login bug',
            'priority' => 'high',
        ])->assertStatus(201);

        Event::assertDispatched(TaskCreated::class, function ($event) {
            return $event->task->title === 'Fix login bug';
        });
    }

    public function test_creating_task_with_assignee_dispatches_notification_job(): void
    {
        Queue::fake();

        $owner    = $this->actingAsUser();
        $assignee = User::factory()->create();
        $project  = Project::factory()->create(['owner_id' => $owner->id]);

        $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title'       => 'Deploy feature',
            'priority'    => 'urgent',
            'assigned_to' => $assignee->id,
        ])->assertStatus(201);

        Queue::assertPushedOn('notifications', SendTaskAssignedNotification::class);
    }

    public function test_task_list_is_filterable_by_status(): void
    {
        $owner   = $this->actingAsUser();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'status' => 'open']);
        Task::factory()->create(['project_id' => $project->id, 'created_by' => $owner->id, 'status' => 'done']);

        $response = $this->getJson("/api/v1/projects/{$project->id}/tasks?status=open");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('open', $data[0]['status']['value']);
    }

    public function test_non_project_member_cannot_view_tasks(): void
    {
        $owner   = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->actingAsUser(); // different user

        $this->getJson("/api/v1/projects/{$project->id}/tasks")
            ->assertStatus(403);
    }
}
```

**Git commit:**
```
test(tasks): add advanced feature tests for event dispatch, Queue::fake, filtering, and policy
```

---

## Step 7.5 — Unit Tests (Service + Filter)

**File: `tests/Unit/Services/TaskServiceTest.php`**
```php
<?php

namespace Tests\Unit\Services;

use App\DTOs\Task\CreateTaskDTO;
use App\Events\TaskCreated;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use App\Support\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaskService::class);
    }

    public function test_create_dispatches_task_created_event(): void
    {
        Event::fake([TaskCreated::class]);

        $user    = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $this->service->create($project, $user, new CreateTaskDTO(
            title:       'Test Task',
            description: null,
            assignedTo:  null,
            priority:    'high',
            dueDate:     null,
        ));

        Event::assertDispatched(TaskCreated::class);
    }

    public function test_new_task_has_open_status_by_default(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $task = $this->service->create($project, $user, new CreateTaskDTO(
            title:       'New Task',
            description: null,
            assignedTo:  null,
            priority:    'medium',
            dueDate:     null,
        ));

        $this->assertEquals(TaskStatus::Open, $task->status);
    }

    public function test_creating_task_touches_project_to_invalidate_cache(): void
    {
        $user    = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        $originalTimestamp = $project->updated_at;

        // Ensure timestamp differs
        sleep(1);

        $this->service->create($project, $user, new CreateTaskDTO(
            title: 'Cache Test', description: null,
            assignedTo: null, priority: 'low', dueDate: null,
        ));

        $this->assertGreaterThan(
            $originalTimestamp,
            $project->fresh()->updated_at
        );
    }
}
```

**Git commit:**
```
test(unit): add unit tests for TaskService event dispatch, default status, and cache invalidation
```

---

## Step 7.6 — Policy Unit Tests

**File: `tests/Unit/Policies/ProjectPolicyTest.php`**
```php
<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProjectPolicy();
    }

    public function test_owner_can_update_project(): void
    {
        $owner   = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($this->policy->update($owner, $project));
    }

    public function test_non_owner_cannot_update_project(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);

        $this->assertFalse($this->policy->update($other, $project));
    }

    public function test_member_can_view_project(): void
    {
        $owner   = User::factory()->create();
        $member  = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        $project->members()->attach($member->id);

        $this->assertTrue($this->policy->view($member, $project));
    }
}
```

**Git commit:**
```
test(unit): add ProjectPolicy unit tests for owner/member/non-member scenarios
```

---

# 12. Phase 8 — Postman Collection Strategy

---

## Step 8.1 — Collection Structure

**Top-level name:** `TaskFlow API v1`

```
📁 TaskFlow API v1
├── 📁 System
│   └── Health Check
├── 📁 Auth
│   ├── Register
│   ├── Login
│   ├── Logout
│   └── Get Me (Authenticated User)
├── 📁 Projects
│   ├── List Projects (paginated)
│   ├── Create Project
│   ├── Get Project
│   ├── Update Project
│   └── Delete Project
├── 📁 Tasks
│   ├── List Tasks (with filter examples)
│   ├── Create Task
│   ├── Get Task (shallow)
│   ├── Update Task Status
│   └── Delete Task
├── 📁 Comments
│   ├── List Comments
│   ├── Post Comment
│   └── Delete Comment
└── 📁 Notifications
    ├── List Notifications
    ├── Mark Notification Read
    └── Mark All Read
```

## Step 8.2 — Environments

**Environment: `TaskFlow — Local`**
```
BASE_URL     = http://localhost:8000/api
AUTH_TOKEN   = (auto-filled by Login script)
PROJECT_ID   = (auto-filled by Create Project script)
TASK_ID      = (auto-filled by Create Task script)
```

**Environment: `TaskFlow — Staging`**
```
BASE_URL     = https://api-staging.taskflow.com/api
AUTH_TOKEN   = (empty)
```

## Step 8.3 — Auto Token + ID Capture Scripts

**Login → Tests tab:**
```javascript
const json = pm.response.json();
if (pm.response.code === 200 && json.data?.token) {
    pm.environment.set("AUTH_TOKEN", json.data.token);
    console.log("Auth token saved.");
}
```

**Create Project → Tests tab:**
```javascript
const json = pm.response.json();
if (pm.response.code === 201) {
    pm.environment.set("PROJECT_ID", json.data.id);
}
```

**Create Task → Tests tab:**
```javascript
const json = pm.response.json();
if (pm.response.code === 201) {
    pm.environment.set("TASK_ID", json.data.id);
}
```

## Step 8.4 — Collection-Level Pre-request Script

```javascript
// Inject request ID for tracing (matches SetRequestId middleware)
pm.request.headers.add({
    key: "X-Request-ID",
    value: "postman-" + pm.variables.replaceIn("{{$guid}}")
});
```

## Step 8.5 — Standard Test Assertions (add to every request)

```javascript
pm.test("Response time under 500ms", () => {
    pm.expect(pm.response.responseTime).to.be.below(500);
});

pm.test("Standard envelope present", () => {
    const json = pm.response.json();
    pm.expect(json).to.have.property("success");
    pm.expect(json).to.have.property("meta");
    pm.expect(json.meta).to.have.property("request_id");
    pm.expect(json.meta).to.have.property("version").that.equals("v1");
});

pm.test("Content-Type is JSON", () => {
    pm.expect(pm.response.headers.get("Content-Type")).to.include("application/json");
});

pm.test("X-Request-ID header present in response", () => {
    pm.expect(pm.response.headers.has("X-Request-ID")).to.be.true;
});
```

**Git commit:**
```
docs(postman): add Postman collection with dual environments, auto-token, ID capture, and assertions
```

---

# 13. Phase 9 — CI/CD Pipeline

---

## Step 9.1 — PR Check Workflow (Lint + Static Analysis + Tests)

**File: `.github/workflows/ci.yml`**
```yaml
name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  lint:
    name: Lint (Laravel Pint)
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none

      - name: Cache Composer
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - run: composer install --no-progress --prefer-dist
      - run: ./vendor/bin/pint --test

  static-analysis:
    name: Static Analysis (PHPStan)
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none

      - name: Cache Composer
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - run: composer install --no-progress --prefer-dist
      - run: composer require --dev phpstan/phpstan larastan/larastan --no-interaction
      - run: ./vendor/bin/phpstan analyse --level=6 app/

  test:
    name: Tests + Coverage
    runs-on: ubuntu-latest

    services:
      redis:
        image: redis:7-alpine
        ports: ["6379:6379"]
        options: --health-cmd="redis-cli ping" --health-interval=10s

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: dom, curl, mbstring, zip, pcntl, pdo, pdo_mysql, redis
          coverage: xdebug

      - name: Cache Composer
        uses: actions/cache@v3
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}

      - run: composer install --no-progress --prefer-dist --optimize-autoloader
      - run: cp .env.example .env
      - run: php artisan key:generate

      - name: Run tests with coverage enforcement
        run: php artisan test --coverage --min=80
        env:
          DB_CONNECTION: sqlite
          DB_DATABASE: ":memory:"
          CACHE_STORE: array
          QUEUE_CONNECTION: sync

      - name: Upload coverage
        uses: actions/upload-artifact@v3
        if: always()
        with:
          name: coverage-report
          path: coverage-report/
```

## Step 9.2 — Deploy Workflow

**File: `.github/workflows/deploy.yml`**
```yaml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  deploy:
    name: Deploy to Production
    runs-on: ubuntu-latest
    needs: []   # Reference the test job from ci.yml in a real repo

    steps:
      - uses: actions/checkout@v4

      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.DEPLOY_HOST }}
          username: ${{ secrets.DEPLOY_USER }}
          key: ${{ secrets.DEPLOY_SSH_KEY }}
          script: |
            cd /var/www/taskflow-api
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan event:cache
            php artisan view:cache
            sudo supervisorctl restart taskflow-worker:*
            echo "Deploy complete."
```

**Git commit:**
```
ci: add GitHub Actions CI workflow with Pint, PHPStan, coverage enforcement, and deploy pipeline
```

---

# 14. Phase 10 — Deployment Strategy

---

## Step 10.1 — Production `.env`

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.taskflow.com
APP_VERSION=1.0.0

LOG_CHANNEL=stderr
LOG_LEVEL=warning

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

CORS_ALLOWED_ORIGINS=https://app.taskflow.com

SANCTUM_STATEFUL_DOMAINS=app.taskflow.com
```

## Step 10.2 — Deployment Commands (run on every deploy)

```bash
php artisan down --retry=60

git pull origin main
composer install --no-dev --optimize-autoloader

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

php artisan up
```

## Step 10.3 — Supervisor Queue Worker Config

```ini
[program:taskflow-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/taskflow-api/artisan queue:work redis \
    --sleep=3 \
    --tries=3 \
    --queue=notifications,default \
    --timeout=90 \
    --max-jobs=500
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/taskflow-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
```

## Step 10.4 — Security Hardening Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `config/cors.php` lists only known frontend origins
- [ ] All tokens use ability scoping (`['read']`, `['write']`)
- [ ] Rate limiting active on auth and write endpoints
- [ ] `X-Content-Type-Options`, `X-Frame-Options` headers on all responses
- [ ] HSTS header active (`Strict-Transport-Security`)
- [ ] `HTTPS` enforced at Nginx/load balancer level
- [ ] Database user has only required permissions (no `DROP`, no `CREATE`)
- [ ] Redis `requirepass` configured in production
- [ ] Logs exclude sensitive fields (`password`, `token`)

**Git commit:**
```
chore(deploy): production env, supervisor config, and security hardening checklist
```

---

# 15. Phase 11 — Database Seeding & Factories

---

## Step 11.1 — Model Factories

**File: `database/factories/ProjectFactory.php`**
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id'    => User::factory(),
            'name'        => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(12),
            'status'      => 'active',
        ];
    }

    public function archived(): static
    {
        return $this->state(['status' => 'archived']);
    }
}
```

**File: `database/factories/TaskFactory.php`**
```php
<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Support\Enums\TaskPriority;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'created_by'  => User::factory(),
            'assigned_to' => null,
            'title'       => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(),
            'status'      => $this->faker->randomElement(TaskStatus::values()),
            'priority'    => $this->faker->randomElement(TaskPriority::values()),
            'due_date'    => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => TaskStatus::Open->value]);
    }

    public function assigned(User $user): static
    {
        return $this->state(['assigned_to' => $user->id]);
    }

    public function overdue(): static
    {
        return $this->state([
            'due_date' => now()->subDays(3)->toDateString(),
            'status'   => TaskStatus::InProgress->value,
        ]);
    }
}
```

**File: `database/factories/CommentFactory.php`**
```php
<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'body'    => $this->faker->paragraph(),
        ];
    }
}
```

**Git commit:**
```
feat(factories): add Project, Task, Comment factories with state methods (open, overdue, assigned, archived)
```

---

## Step 11.2 — DatabaseSeeder

**File: `database/seeders/DatabaseSeeder.php`**
```php
<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Known demo user — login immediately without registering ──
        $demo = User::factory()->create([
            'name'     => 'Demo User',
            'email'    => 'demo@taskflow.com',
            'password' => Hash::make('password'),
        ]);

        $member = User::factory()->create([
            'name'     => 'Jane Member',
            'email'    => 'jane@taskflow.com',
            'password' => Hash::make('password'),
        ]);

        // ── Project 1: multi-member with tasks and comments ──
        $project1 = Project::factory()->create([
            'owner_id' => $demo->id,
            'name'     => 'Website Redesign',
        ]);

        $project1->members()->attach($member->id, ['role' => 'member']);

        // Mix of open + overdue tasks, all assigned, with comments
        Task::factory(5)->open()->assigned($demo)->create([
            'project_id' => $project1->id,
            'created_by' => $demo->id,
        ])->each(fn ($t) => Comment::factory(rand(2, 4))->create([
            'task_id' => $t->id,
            'user_id' => $demo->id,
        ]));

        Task::factory(3)->overdue()->assigned($member)->create([
            'project_id' => $project1->id,
            'created_by' => $demo->id,
        ])->each(fn ($t) => Comment::factory(2)->create([
            'task_id' => $t->id,
            'user_id' => $member->id,
        ]));

        // ── Project 2: solo project with done tasks ──
        $project2 = Project::factory()->create([
            'owner_id' => $demo->id,
            'name'     => 'Mobile App Launch',
        ]);

        Task::factory(5)->create([
            'project_id'  => $project2->id,
            'created_by'  => $demo->id,
            'assigned_to' => $demo->id,
        ])->each(fn ($t) => Comment::factory(1)->create([
            'task_id' => $t->id,
            'user_id' => $demo->id,
        ]));

        // ── Project 3: archived (tests filter behavior) ──
        Project::factory()->archived()->create([
            'owner_id' => $demo->id,
            'name'     => 'Old Q1 Initiative',
        ]);

        $this->command->info('');
        $this->command->info('TaskFlow API seeded successfully.');
        $this->command->info('');
        $this->command->table(
            ['Field', 'Value'],
            [
                ['Primary email',    'demo@taskflow.com'],
                ['Primary password', 'password'],
                ['Member email',     'jane@taskflow.com'],
                ['Member password',  'password'],
                ['Projects',         '3 (2 active, 1 archived)'],
                ['Tasks',            '13 total (5 open, 3 overdue, 5 mixed)'],
                ['Comments',         '~25 across all tasks'],
            ]
        );
    }
}
```

**Commands:**
```bash
# Fresh seed (wipes and re-seeds)
php artisan migrate:fresh --seed

# Docker:
docker-compose exec app php artisan migrate:fresh --seed
```

**Checklist:**
- [ ] `php artisan migrate:fresh --seed` completes cleanly
- [ ] `POST /api/v1/auth/login` with `demo@taskflow.com` / `password` returns token
- [ ] `GET /api/v1/projects` returns 3 projects
- [ ] `GET /api/v1/projects/{id}/tasks?status=open` returns 5 open tasks
- [ ] `GET /api/v1/projects/{id}/tasks?overdue=true` returns 3 overdue tasks

**Git commit:**
```
feat(seeders): add DatabaseSeeder with demo users, mixed-state tasks, and overdue data
```

---

# 16. Final Commit History

Complete ordered Git commit history following Conventional Commits format.

```
chore(env): switch CACHE_STORE and QUEUE_CONNECTION from database to redis
feat(bootstrap): configure Laravel 12 routing, middleware, and exception handling in bootstrap/app.php
feat(cors): add config/cors.php with environment-based allowed origins
feat(middleware): ensure ForceJsonResponse is registered in bootstrap/app.php API group
feat(middleware): implement SetRequestId with container binding and job propagation
feat(middleware): add SecurityHeaders middleware with HSTS, XSS, and frame protection
feat(support): implement standardized ApiResponse envelope helper
feat(logging): add structured JSON logging with request context for Datadog/ELK ingest
feat(ratelimit): configure Redis-backed rate limiting with per-verb and per-group limits
feat(routes): establish versioned API route structure with health check and rate limit groups
feat(docs): integrate dedoc/scramble for zero-config OpenAPI 3.1 documentation
chore(docker): multi-stage Dockerfile, .dockerignore, healthchecks, persistent volumes
feat(auth): add HasApiTokens to User model, publish and run Sanctum migration
feat(dto): add RegisterDTO as typed data carrier from request to service
feat(auth): add RegisterRequest and LoginRequest form validation classes
feat(auth): implement AuthService using RegisterDTO for typed input passing
feat(auth): implement AuthController with DTO pattern and UserResource
feat(projects): add projects and project_user migrations with composite soft-delete indexes
feat(dto): add CreateProjectDTO and UpdateProjectDTO
feat(projects): implement ProjectPolicy with owner/member authorization rules
feat(projects): add ProjectRepository with user-scoped query and dual soft-delete-aware task counts
feat(projects): implement ProjectService with DTO input, Redis cache, and tag-based invalidation
feat(projects): implement ProjectController with DTO pattern and ProjectResource
feat(tasks): add TaskStatus and TaskPriority PHP 8.1 backed enums with helper methods
feat(tasks): add tasks migration with composite indexes, Task model with enum casts
feat(filters): add TaskQueryFilter with composable filter chain pattern
feat(dto): add CreateTaskDTO and UpdateTaskDTO
feat(events): add TaskCreated, TaskStatusChanged, CommentPosted — event-sourcing compatible value objects
feat(tasks): implement TaskService with TaskQueryFilter, DTO input, and Russian Doll caching
feat(actions): add AssignTaskAction and ArchiveProjectAction as single-purpose action classes
feat(tasks): implement TaskController with DTO pattern, filter params, and TaskResource
feat(comments): add comments migration with soft-delete index, Comment model
feat(comments): implement CommentService with DB::transaction and after-commit event dispatch
feat(notifications): add database notifications table with composite read_at index
feat(notifications): implement queued notification job with request_id propagation and after-commit dispatch
feat(notifications): implement NotificationController with read/read-all endpoints
test(config): configure PHPUnit with in-memory SQLite, array cache, and sync queue
test(auth): add feature tests for register, login, logout, and me endpoints
test(projects): add feature tests for CRUD, policy enforcement, and soft delete
test(tasks): add advanced feature tests for event dispatch, Queue::fake, filtering, and policy
test(unit): add unit tests for TaskService event dispatch, default status, and cache invalidation
test(unit): add ProjectPolicy unit tests for owner/member/non-member scenarios
docs(postman): add Postman collection with dual environments, auto-token, ID capture, and assertions
ci: add GitHub Actions CI workflow with Pint, PHPStan, coverage enforcement, and deploy pipeline
chore(deploy): production env, supervisor config, and security hardening checklist
feat(factories): add Project, Task, Comment factories with state methods (open, overdue, assigned, archived)
feat(seeders): add DatabaseSeeder with demo users, mixed-state tasks, and overdue data
```

---

# Appendix A — Database Index Summary

| Table | Index | Justification |
|-------|-------|--------------|
| `projects` | `(owner_id, status)` | Primary owner listing with status filter |
| `projects` | `(owner_id, deleted_at)` | Soft-delete scoped owner queries |
| `projects` | `(deleted_at)` | Global scope on all project queries |
| `project_user` | `(user_id, project_id)` | Membership lookup (is user a member?) |
| `tasks` | `(project_id, status)` | Primary task filter |
| `tasks` | `(project_id, priority)` | Priority filter within project |
| `tasks` | `(project_id, deleted_at)` | Soft-delete scoped project task queries |
| `tasks` | `(assigned_to, status)` | "My tasks" view |
| `tasks` | `(due_date)` | Overdue task queries |
| `comments` | `(task_id, created_at)` | Chronological comment thread |
| `comments` | `(task_id, deleted_at)` | Soft-delete scoped comment queries |
| `notifications` | `(notifiable_type, notifiable_id, read_at)` | Unread notification fetch |

---

# Appendix B — N+1 Prevention Rules

| Scenario | Rule |
|----------|------|
| Project list | Always `with(['owner:id,name,email'])` |
| Task list | Always `with(['creator:id,name', 'assignee:id,name,email'])` |
| Comment list | Always `with(['author:id,name,email'])` |
| Task counts | Use named `withCount([...])`, never `$project->tasks->count()` |
| Conditional loading in Resources | Use `$this->whenLoaded('relation')` — never eager load in Resource |
| TaskQueryFilter | `withEagerLoads()` always called as part of the filter chain |

---

# Appendix C — Redis Cache Key Strategy

| Key Pattern | TTL | Invalidated By |
|-------------|-----|----------------|
| `user:{id}:projects:page:{n}` | 5 min | `Cache::tags(["user:{id}:projects"])->flush()` on any project write |
| `project:{id}:v{updated_at_ts}:tasks:{filter_md5}` | 5 min | `$project->touch()` — changes `updated_at`, all old keys become unreachable |

**Russian Doll Caching — Mechanism:**

```
Before task create:  project:42:v1700000000:tasks:abc123  ← served from cache
$project->touch() → updated_at changes to 1700000099
After task create:   project:42:v1700000099:tasks:abc123  ← new key, cache miss → DB
                     project:42:v1700000000:tasks:abc123  ← unreachable, expires via TTL
```

No explicit flush. No tag management. Any cache driver works (not just Redis).

---

# Appendix D — Senior Engineer Interview Talking Points

This section prepares you to speak confidently about every design decision in this project.

---

**"Why Services over fat controllers?"**

Controllers in this project do exactly three things: validate input (via FormRequest), build a DTO, and return an ApiResponse. All business logic lives in Services. This separation means you can test business logic in unit tests without booting HTTP — faster, cheaper, more focused tests. It also means if you swap the delivery mechanism (e.g., add an Artisan command or a webhook handler), the service is reused unchanged.

---

**"What is a DTO and why use it here?"**

A DTO (Data Transfer Object) is a typed, readonly value object that carries data between layers. Instead of passing `$request->validated()` (an untyped array) into a service, we pass `CreateTaskDTO::fromRequest($request)`. This makes service method signatures self-documenting, enables IDE autocompletion, eliminates array-key typos, and makes it trivial to spot what data a service actually needs without reading its entire implementation.

---

**"Why a QueryFilter class instead of inline `when()` calls?"**

The `TaskQueryFilter` class isolates all task query logic in one place. It is individually testable (`TaskQueryFilter::apply(['status' => 'open'])`), it makes the service method one line, and adding a new filter (e.g., `due_date_range`) requires changing only one file. Inline `when()` chains in services grow indefinitely and are not individually testable.

---

**"Why Russian Doll Caching instead of Cache::tags()->flush()?"**

Cache tag flushing requires Redis — it breaks on other drivers. Russian Doll caching embeds `$project->updated_at` as a version segment in the cache key. When a task changes, `$project->touch()` increments `updated_at`, which changes the key. Old cached variants simply stop being requested and expire naturally via TTL. This works on any cache driver, requires one line of code per write operation, and is impossible to misconfigure. The trade-off is slightly more Redis memory (orphaned old keys) — which TTL eliminates.

---

**"Why shallow nested routes?"**

`Route::apiResource('projects.tasks', TaskController::class)->shallow()` generates nested routes for the collection operations (`GET /projects/{project}/tasks`, `POST /projects/{project}/tasks`) but individual task operations use `/tasks/{task}` without the project prefix. This avoids `/projects/{project}/tasks/{task}` — a URL that includes redundant context the server already has via `{task}`. Shallow routes are shorter, easier to bookmark, and avoid the need to pass both `project_id` and `task_id` in every client request.

---

**"Why FormRequest over manual validation in controllers?"**

FormRequests enforce a single responsibility: they own all validation and authorization logic for a given request type. They are independently testable, they produce consistent 422 responses via the global exception handler, and they keep controllers down to 3–5 lines of logic per method. Manually calling `$request->validate([...])` in controllers leads to duplicated validation logic and fat controllers.

---

**"Why soft deletes everywhere?"**

Soft deletes mean deleted records stay in the database with a `deleted_at` timestamp. This enables: audit trails (you can see what was deleted and when), accidental-deletion recovery, referential integrity (a deleted task's comments still exist), and analytics on historical data. The composite index on `(project_id, deleted_at)` ensures soft-delete scoping doesn't add a full table scan.

---

**"Why dispatch events from Services, not Controllers?"**

If a controller fires an event, you can't reuse the same business operation from an Artisan command, a webhook, or a scheduled job without duplicating the event dispatch. Services own the full operation — including its side effects. Events emitted from services are consistent regardless of how the service was invoked.

---

**"Why `DB::transaction` around comment creation?"**

Without a transaction, if `Comment::create()` succeeds and then a subsequent operation fails (triggering a rollback), the `CommentPosted` event has already been dispatched. The queued job fires, attempts to load the comment, and finds nothing — a silent failure or unhandled exception in the worker. Wrapping in `DB::transaction` with `$afterCommit = true` on the job ensures the job only executes if the DB commit actually succeeded.

---

**"Why propagate `request_id` into queued jobs?"**

When a notification job fails or produces a log entry, you need to trace it back to the original HTTP request that triggered it. By passing `request_id` from the middleware → event → listener → job, every async log line can be correlated with the exact API call that initiated it. This is table-stakes for any distributed system with async workers.

---

**"Why event-sourcing compatible events?"**

Each domain event (`TaskCreated`, `TaskStatusChanged`) is a readonly, serializable value object that records what happened, not how to handle it. This design is intentional: if the project later adopts `spatie/laravel-event-sourcing`, these events require no structural changes — they can be stored in an event store as-is. It also means you can replay events to rebuild state, which is impossible with fat listeners that contain both detection and reaction logic.

---

**"What would you change to scale this to 10M requests/day?"**

- Add a read replica for all `SELECT` queries via `DB::connection('replica')`
- Implement cursor-based pagination instead of offset pagination (avoids `OFFSET` performance cliff at high page numbers)
- Move the Redis cache to a dedicated cluster with eviction policy `allkeys-lru`
- Add a CDN-layer API cache for public, low-churn endpoints
- Partition the `tasks` table by `project_id` range at 100M+ rows
- Extract the notification system to a dedicated microservice using a message broker (RabbitMQ, SQS)

---

*End of Document — TaskFlow API Engineering Playbook v2.0.0 · Laravel 12 Edition*
