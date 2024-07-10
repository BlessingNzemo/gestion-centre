<?php

namespace App\Models;

use App\Models\Candidat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CandidatAttribute extends Model
{
    use HasFactory;
    protected $fillable = [
        '_id',
        'label',
        'value',
        'candidat_id'
    ] ;

    public function candidat() : BelongsTo {
        return $this->belongsTo(Candidat::class);
    }
}
