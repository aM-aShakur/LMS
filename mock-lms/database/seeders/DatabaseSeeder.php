<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);
        // Add module seeders here once created:
        // $this->call(\App\Modules\Learning\Course\Database\Seeders\CourseSeeder::class);
        // $this->call(\App\Modules\Learning\Lesson\Database\Seeders\LessonSeeder::class);
    }
}
