<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localization extends Model
{
    protected $guarded = [];
    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName() : string
    {
        return 'id_key';
    }
}