<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Traceability extends Model
{
    use HasFactory;

    protected $fillable = [
        'coffee_id', 'farmer', 'region', 'altitude',
        'varietal', 'process', 'harvest_date', 'q_grader',
    ];

    protected function casts(): array
    {
        return [
            'harvest_date' => 'date',
            'altitude' => 'integer',
        ];
    }

    public function coffee(): BelongsTo
    {
        return $this->belongsTo(Coffee::class);
    }

    public function getAltitudeLabelAttribute(): string
    {
        return number_format($this->altitude) . ' masl';
    }
}
