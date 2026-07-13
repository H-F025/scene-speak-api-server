<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    use HasFactory;

    public function themeLevel(): BelongsTo
    {
        return $this->belongsTo(ThemeLevel::class);
    }

        public function choices(): HasMany
    {
        return $this->hasMany(QuestionChoice::class);
    }

    public function categories(): BelongsToMany
    {
    return $this->belongsToMany(QuestionCategory::class, 'question_category_assignments');
    }
}