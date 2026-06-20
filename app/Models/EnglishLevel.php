<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Guarded(['id', 'created_at', 'updated_at'])]
class EnglishLevel extends Model
{
    use HasFactory;
}
