<?php

namespace Tests\Feature\EnglishLevel;

use App\Models\EnglishLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var array<EnglishLevel> */
    private array $englishLevels;

    protected function setUp(): void
    {
        parent::setUp();

        $this->englishLevels = [
            EnglishLevel::factory()->create(['code' => 'beginner', 'sort_order' => 1]),
            EnglishLevel::factory()->create(['code' => 'intermediate', 'sort_order' => 2]),
            EnglishLevel::factory()->create(['code' => 'advanced', 'sort_order' => 3]),
        ];

        $this->user = User::factory()->create([
            'english_level_id' => $this->englishLevels[0]->id,
        ]);
    }

    public function test_returns_english_levels_in_sort_order(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/english-levels');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'english_levels' => [
                    '*' => ['id', 'code', 'name', 'description', 'example_sentence'],
                ],
            ]);

        $returnedIds = array_column($response->json('english_levels'), 'id');
        $expectedIds = array_map(fn ($level) => $level->id, $this->englishLevels);
        $this->assertSame($expectedIds, $returnedIds);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/english-levels');

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }
}