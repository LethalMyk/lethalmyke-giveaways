<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HuntSuggestion extends Model
{
    protected $fillable = ['hunt_id', 'hunt_name', 'submitter_name', 'message', 'status'];

    public function hunt(): BelongsTo
    {
        return $this->belongsTo(Hunt::class);
    }
}