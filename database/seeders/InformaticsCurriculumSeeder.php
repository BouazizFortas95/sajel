<?php

namespace Database\Seeders;

use App\Models\CurriculumDomain;
use App\Models\CurriculumResource;
use App\Models\CurriculumSequence;
use App\Models\Level;
use App\Models\Subject;
use App\Services\AnnualCurriculumDistributionService;
use Illuminate\Database\Seeder;

class InformaticsCurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = app(AnnualCurriculumDistributionService::class);

        $subject = Subject::where('name', 'like', '%إعلام%آلي%')
            ->orWhere('name', 'like', '%معلوماتية%')
            ->first();

        if (! $subject) {
            $subject = Subject::create([
                'name' => 'الإعلام الآلي',
                'code' => 'INFO',
            ]);
        }

        $levelMapping = [
            1 => Level::where('name', 'like', '%الأولى%متوسط%')->first(),
            2 => Level::where('name', 'like', '%الثانية%متوسط%')->first(),
            3 => Level::where('name', 'like', '%الثالثة%متوسط%')->first(),
            4 => Level::where('name', 'like', '%الرابعة%متوسط%')->first(),
        ];

        foreach ($levelMapping as $lvlNum => $level) {
            if (! $level) {
                continue;
            }

            $levelData = $service->getLevelData($lvlNum);
            $domainOrder = 1;

            foreach ($levelData['domains'] as $dData) {
                $domain = CurriculumDomain::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'level_id' => $level->id,
                        'name' => $dData['name'],
                    ],
                    [
                        'description' => strip_tags(str_replace('<br>', ' ', $dData['competence'])),
                        'sort_order' => $domainOrder++,
                    ]
                );

                $currentSequence = null;
                $sequenceOrder = 1;
                $resourceOrder = 1;

                foreach ($dData['rows'] as $row) {
                    if (! empty($row['is_break'])) {
                        continue;
                    }

                    if (! empty($row['sequence'])) {
                        $seqName = $row['sequence']['name'];
                        $currentSequence = CurriculumSequence::updateOrCreate(
                            [
                                'curriculum_domain_id' => $domain->id,
                                'name' => $seqName,
                            ],
                            [
                                'sort_order' => $sequenceOrder++,
                            ]
                        );
                    }

                    if ($currentSequence && ! empty($row['lesson'])) {
                        $resName = $row['lesson'];
                        CurriculumResource::updateOrCreate(
                            [
                                'curriculum_sequence_id' => $currentSequence->id,
                                'name' => $resName,
                            ],
                            [
                                'description' => $row['hours'] ?? '01 ساعة',
                                'sort_order' => $resourceOrder++,
                            ]
                        );
                    }
                }
            }
        }
    }
}

