<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('email', 'demo@taskflow.com')->first();
        $jane = User::where('email', 'jane@taskflow.com')->first();

        $project1 = Project::create([
            'owner_id' => $demo->id,
            'name' => 'Website Redesign',
            'description' => 'Complete redesign of the company website with modern UI/UX.',
            'status' => 'active',
        ]);

        $project1->members()->attach($jane->id, ['role' => 'member']);

        Project::create([
            'owner_id' => $demo->id,
            'name' => 'Mobile App Launch',
            'description' => 'Launch the mobile application for iOS and Android.',
            'status' => 'active',
        ]);

        Project::create([
            'owner_id' => $demo->id,
            'name' => 'Old Q1 Initiative',
            'description' => 'Archived project from Q1.',
            'status' => 'archived',
        ]);
    }
}
