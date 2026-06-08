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
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
