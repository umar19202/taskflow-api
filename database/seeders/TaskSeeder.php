<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('email', 'demo@taskflow.com')->first();
        $jane = User::where('email', 'jane@taskflow.com')->first();

        $project1 = Project::where('name', 'Website Redesign')->first();
        $project2 = Project::where('name', 'Mobile App Launch')->first();

        Task::factory(5)->assignedTo($demo)->create([
            'project_id' => $project1->id,
            'created_by' => $demo->id,
            'status' => 'open',
        ]);

        Task::factory(3)->assignedTo($jane)->create([
            'project_id' => $project1->id,
            'created_by' => $demo->id,
            'status' => 'in_progress',
            'due_date' => now()->subDays(3)->toDateString(),
        ]);

        Task::factory(5)->assignedTo($demo)->create([
            'project_id' => $project2->id,
            'created_by' => $demo->id,
            'status' => 'done',
        ]);
    }
}
