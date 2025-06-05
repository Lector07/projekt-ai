<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Dodaj jeśli używasz DoctorProcedureFactory

class DoctorProcedure extends Pivot
{
    use HasFactory;

    protected $table = 'doctor_procedure';

    public $incrementing = false;
    protected $primaryKey = ['doctor_id', 'procedure_id'];

    public $timestamps = false;

    protected $fillable = [
        'doctor_id',
        'procedure_id',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class, 'procedure_id');
    }
}
