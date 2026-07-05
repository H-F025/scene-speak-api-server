<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeLevel extends Model
{
    use HasFactory;

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function englishLevel(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class);
    }
}