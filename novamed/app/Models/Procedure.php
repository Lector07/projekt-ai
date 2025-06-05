<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_price',
        'procedure_category_id',
        'recovery_timeline_info',
        'duration_minutes',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProcedureCategory::class, 'procedure_category_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'procedure_id');
    }
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_procedure', 'procedure_id', 'doctor_id');
    }
}
