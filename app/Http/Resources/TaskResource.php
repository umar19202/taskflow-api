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
                'value'       => $this->status->value,
                'label'       => $this->status->label(),
                'is_terminal' => $this->status->isTerminal(),
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
