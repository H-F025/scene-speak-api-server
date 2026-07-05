<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionProgress extends Model
{
    use HasFactory;

    // progressesは不可算名詞のため、Laravelが複数形を識別できないため、テーブル名を明示する
    protected $table = 'question_progresses';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function themeLearningProgress(): BelongsTo
    {
        return $this->belongsTo(ThemeLearningProgress::class);
    }
}