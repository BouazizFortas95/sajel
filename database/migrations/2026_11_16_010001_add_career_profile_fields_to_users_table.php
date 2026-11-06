<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_number')->nullable()->after('email'); // رقم التعريف المالي / الوظيفي
            $table->string('national_id_number')->nullable()->after('registration_number'); // رقم التعريف الوطني NIN
            $table->date('birth_date')->nullable()->after('national_id_number'); // تاريخ الميلاد
            $table->string('birth_place')->nullable()->after('birth_date'); // مكان الميلاد
            $table->string('gender')->nullable()->after('birth_place'); // الجنس (ذكر / أنثى)
            $table->string('phone')->nullable()->after('gender'); // رقم الهاتف
            $table->date('recruitment_date')->nullable()->after('role'); // تاريخ أول تعيين في الوظيفة العمومية
            $table->date('grade_appointment_date')->nullable()->after('recruitment_date'); // تاريخ التعيين في الرتبة الحالية
            $table->unsignedTinyInteger('current_step')->default(0)->after('grade_appointment_date'); // الدرجة الحالية (0 إلى 12)
            $table->date('current_step_date')->nullable()->after('current_step'); // تاريخ سريان مفعول الدرجة الحالية
            $table->string('statutory_grade')->nullable()->after('current_step_date'); // الرتبة القانونية
            $table->string('diploma')->nullable()->after('statutory_grade'); // الشهادة أو المؤهل العلمي
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'registration_number',
                'national_id_number',
                'birth_date',
                'birth_place',
                'gender',
                'phone',
                'recruitment_date',
                'grade_appointment_date',
                'current_step',
                'current_step_date',
                'statutory_grade',
                'diploma',
            ]);
        });
    }
};

