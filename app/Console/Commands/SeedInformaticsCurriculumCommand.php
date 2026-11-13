<?php

namespace App\Console\Commands;

use Database\Seeders\InformaticsCurriculumSeeder;
use Illuminate\Console\Command;

class SeedInformaticsCurriculumCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sajel:seed-informatics-curriculum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed authentic Algerian middle school Informatics curriculum (domains, sequences, resources) for levels 1AM through 4AM';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding authentic Algerian middle school Informatics curriculum...');

        $this->call(InformaticsCurriculumSeeder::class);

        $this->info('Informatics curriculum successfully seeded for all middle school levels (1AM to 4AM).');

        return self::SUCCESS;
    }
}
