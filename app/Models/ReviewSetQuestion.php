<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id', 'created_at', 'updated_at'])]
class ReviewSetQuestion extends Model
{
    use HasFactory;

    public function reviewSet(): BelongsTo
    {
        return $this->belongsTo(ReviewSet::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function questionAttempt(): BelongsTo
    {
        return $this->belongsTo(QuestionAttempt::class);
    }
}
