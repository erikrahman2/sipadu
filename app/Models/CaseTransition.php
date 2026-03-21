<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseTransition extends Model
{
    protected $fillable = [
        'case_id', 'from_state', 'to_state',
        'transitioned_by', 'reason', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'transitioned_by');
    }
}
