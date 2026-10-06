<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportPhoto extends Model
{
    protected $fillable = ['path', 'thumb_path', 'size', 'sort'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
