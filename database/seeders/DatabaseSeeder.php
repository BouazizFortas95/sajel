<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * The middle-school seeder is destructive (it wipes school and user data),
     * which is exactly what `migrate:fresh --seed` and local demos need. Do not
     * keep `WithoutModelEvents` here: the Classroom naming hook must run.
     */
    public function run(): void
    {
        $this->call(MiddleSchoolDatabaseSeeder::class);
    }
}
