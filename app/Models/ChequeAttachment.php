<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChequeAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cheque_id',
        'file_path',
        'file_type',
        'uploaded_by',
    ];

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }
}
