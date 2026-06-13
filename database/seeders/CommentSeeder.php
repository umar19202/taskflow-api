<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('email', 'demo@taskflow.com')->first();
        $jane = User::where('email', 'jane@taskflow.com')->first();

        $tasks = Task::all();

        foreach ($tasks as $task) {
            if ($task->assigned_to === $demo->id) {
                Comment::factory(rand(2, 4))->create([
                    'task_id' => $task->id,
                    'user_id' => $demo->id,
                ]);
            } else {
                Comment::factory(2)->create([
                    'task_id' => $task->id,
                    'user_id' => $jane->id,
                ]);
            }
        }
    }
}
