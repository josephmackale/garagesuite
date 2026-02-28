<?php

// app/Models/GarageLegalDocument.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarageLegalDocument extends Model
{
    protected $table = 'garage_legal_documents';

    protected $fillable = [
        'garage_id','doc_type','path','original_name','mime','size','uploaded_by','uploaded_at',
    ];
}