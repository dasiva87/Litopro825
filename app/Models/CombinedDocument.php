<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo virtual para mostrar documentos combinados en la vista de proyectos.
 * No tiene tabla real, se usa con fromSub() para consultas UNION.
 */
class CombinedDocument extends Model
{
    protected $table = 'combined_docs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];
}
