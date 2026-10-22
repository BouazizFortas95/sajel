<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hadLegacySubjectColumn = Schema::hasColumn('users', 'subject_id');

        if ($hadLegacySubjectColumn && ! Schema::hasColumn('users', 'primary_subject_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('subject_id', 'primary_subject_id');
            });
        }

        $addPrimarySubject = ! Schema::hasColumn('users', 'primary_subject_id');
        $addSecondarySubject = ! Schema::hasColumn('users', 'secondary_subject_id');
        $addWeeklyHoursLimit = ! Schema::hasColumn('users', 'weekly_hours_limit');

        if ($addPrimarySubject || $addSecondarySubject || $addWeeklyHoursLimit) {
            Schema::table('users', function (Blueprint $table) use (
                $addPrimarySubject,
                $addSecondarySubject,
                $addWeeklyHoursLimit,
            ) {
                if ($addPrimarySubject) {
                    $table->foreignId('primary_subject_id')
                        ->nullable()
                        ->after('role')
                        ->constrained('subjects')
                        ->nullOnDelete();
                }

                if ($addSecondarySubject) {
                    $table->foreignId('secondary_subject_id')
                        ->nullable()
                        ->after('primary_subject_id')
                        ->constrained('subjects')
                        ->nullOnDelete();
                }

                if ($addWeeklyHoursLimit) {
                    $table->integer('weekly_hours_limit')
                        ->default(22)
                        ->after('secondary_subject_id');
                }
            });
        }

        $this->backfillTeacherSubjects();
    }

    /**
     * Preserve existing teachers' timetables after introducing the two-subject
     * limit. The first two distinct subjects already scheduled become the
     * primary and secondary assignments when no assignment exists yet.
     */
    private function backfillTeacherSubjects(): void
    {
        DB::table('users')
            ->where('role', 'teacher')
            ->whereNull('primary_subject_id')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $subjectIds = DB::table('schedules')
                        ->where('user_id', $user->id)
                        ->whereNotNull('subject_id')
                        ->orderBy('id')
                        ->pluck('subject_id')
                        ->unique()
                        ->values();

                    if ($subjectIds->isEmpty()) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'primary_subject_id' => $subjectIds->first(),
                            'secondary_subject_id' => $subjectIds->get(1),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('secondary_subject_id');
            $table->dropConstrainedForeignId('primary_subject_id');
            $table->dropColumn('weekly_hours_limit');
        });
    }
};
