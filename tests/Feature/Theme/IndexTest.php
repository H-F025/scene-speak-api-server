<?php

namespace Tests\Feature\Theme;

use App\Models\EnglishLevel;
use App\Models\Theme;
use App\Models\ThemeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private EnglishLevel $beginner;

    private EnglishLevel $intermediate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->beginner     = EnglishLevel::factory()->create(['code' => 'beginner',     'name' => '初級', 'sort_order' => 1]);
        $this->intermediate = EnglishLevel::factory()->create(['code' => 'intermediate', 'name' => '中級', 'sort_order' => 2]);
        EnglishLevel::factory()->create(['code' => 'advanced', 'name' => '上級', 'sort_order' => 3]);

        $this->user = User::factory()->create([
            'english_level_id' => $this->beginner->id,
        ]);
    }

    public function test_returns_themes_filtered_by_specified_english_level(): void
    {
        $theme1 = Theme::factory()->create(['name' => 'カフェで注文',    'sort_order' => 1]);
        $theme2 = Theme::factory()->create(['name' => '空港でチェックイン', 'sort_order' => 2]);

        $themeLevel1 = ThemeLevel::factory()->create([
            'theme_id'         => $theme1->id,
            'english_level_id' => $this->beginner->id,
            'estimated_minutes' => 10,
            'sort_order'       => 1,
        ]);
        $themeLevel2 = ThemeLevel::factory()->create([
            'theme_id'         => $theme2->id,
            'english_level_id' => $this->beginner->id,
            'estimated_minutes' => 15,
            'sort_order'       => 2,
        ]);
        ThemeLevel::factory()->create([
            'theme_id'         => $theme1->id,
            'english_level_id' => $this->intermediate->id,
            'estimated_minutes' => 20,
            'sort_order'       => 3,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/themes?english_level=beginner');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'themes' => [
                    '*' => ['id', 'theme_level_id', 'title', 'description', 'english_level', 'english_level_label', 'estimated_minutes'],
                ],
            ])
            ->assertJsonCount(2, 'themes')
            ->assertJsonPath('themes.0.id', $theme1->id)
            ->assertJsonPath('themes.0.theme_level_id', $themeLevel1->id)
            ->assertJsonPath('themes.0.title', 'カフェで注文')
            ->assertJsonPath('themes.0.english_level', 'beginner')
            ->assertJsonPath('themes.0.english_level_label', '初級')
            ->assertJsonPath('themes.0.estimated_minutes', 10)
            ->assertJsonPath('themes.1.id', $theme2->id)
            ->assertJsonPath('themes.1.theme_level_id', $themeLevel2->id);
    }

    public function test_returns_themes_using_users_english_level_when_not_specified(): void
    {
        $theme = Theme::factory()->create(['sort_order' => 1]);

        ThemeLevel::factory()->create([
            'theme_id'         => $theme->id,
            'english_level_id' => $this->beginner->id,
            'sort_order'       => 1,
        ]);
        ThemeLevel::factory()->create([
            'theme_id'         => $theme->id,
            'english_level_id' => $this->intermediate->id,
            'sort_order'       => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/themes');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'themes')
            ->assertJsonPath('themes.0.english_level', 'beginner');
    }

    public function test_returns_null_estimated_minutes_when_no_limit(): void
    {
        $theme = Theme::factory()->create(['sort_order' => 1]);
        ThemeLevel::factory()->create([
            'theme_id'          => $theme->id,
            'english_level_id'  => $this->beginner->id,
            'estimated_minutes' => null,
            'sort_order'        => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/themes?english_level=beginner');

        $response->assertStatus(200)
            ->assertJsonPath('themes.0.estimated_minutes', null);
    }

    public function test_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/themes');

        $response->assertStatus(401)
            ->assertJson(['message' => '認証が必要です。']);
    }

    public function test_returns_422_when_invalid_english_level(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/themes?english_level=invalid');

        $response->assertStatus(422)
            ->assertJsonPath('errors.english_level.0', '英語レベルはbeginner、intermediate、advancedのいずれかを指定してください。');
    }
}
