<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Guarded;

#[Guarded(['id', 'created_at', 'updated_at'])]
class ThemeLearningProgress extends Model
    {
    use HasFactory;

    // progressesは不可算名詞のため、Laravelが複数形を識別できないため、テーブル名を明示する
    protected $table = 'theme_learning_progresses';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function themeLevel(): BelongsTo
    {
        return $this->belongsTo(ThemeLevel::class);
    }
}