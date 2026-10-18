<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTaskProgress extends Model
{
    protected $table = 'user_task_progress';

    protected $fillable = [
        'user_id',
        'task_id',
        'kills',
        'completed',
    ];

    protected function casts(): array
    {
        return [
            'kills' => 'integer',
            'completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
