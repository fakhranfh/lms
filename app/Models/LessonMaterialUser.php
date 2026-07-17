<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LessonMaterialUser extends Pivot
{
    protected $table = 'lesson_material_user';

    protected $fillable = ['lesson_material_id', 'user_id', 'accessed_at'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'accessed_at' => 'datetime',
    ];
}
