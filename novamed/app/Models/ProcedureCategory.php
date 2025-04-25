<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcedureCategory extends Model
{
    use HasFactory;

    protected $table = 'procedure_categories';

    protected $fillable = ['name', 'slug'];

    public function procedures(): HasMany
    {
        return $this->hasMany(Procedure::class, 'procedure_category_id');
    }
}
