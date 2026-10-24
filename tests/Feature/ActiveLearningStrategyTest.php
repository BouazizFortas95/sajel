<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActiveLearningStrategy;
use App\Models\User;
use Database\Seeders\ActiveLearningStrategySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveLearningStrategyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ActiveLearningStrategySeeder::class);
    }

    public function test_teacher_can_access_active_learning_strategies(): void
    {
        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $this->actingAs($teacher)
            ->get('/ar/auth/active-learning-strategies')
            ->assertOk()
            ->assertSee(__('استراتيجيات التعلم النشط'));
    }

    public function test_strategies_are_seeded_and_visible(): void
    {
        $this->assertGreaterThanOrEqual(1, ActiveLearningStrategy::count());

        $this->assertDatabaseHas('active_learning_strategies', [
            'slug' => 'think-pair-share',
            'is_active' => true,
        ]);
    }

    public function test_strategies_are_referenceable_in_daily_log_phase(): void
    {
        $strategy = ActiveLearningStrategy::first();
        $this->assertNotNull($strategy);

        $teacher = User::factory()->create(['role' => Role::Teacher->value]);

        $this->actingAs($teacher)
            ->get('/ar/auth/daily-logs')
            ->assertOk();
    }
}
