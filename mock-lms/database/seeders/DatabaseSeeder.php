<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --- Global seed data ---
        $this->call(UserSeeder::class);

        // --- Module seeders ---
        $this->call([
            \App\Modules\Learning\Course\Database\Seeders\CourseSeeder::class,
            \App\Modules\Learning\Lesson\Database\Seeders\LessonSeeder::class,
        ]);
    }
}
