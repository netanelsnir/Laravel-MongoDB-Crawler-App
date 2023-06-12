<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Website extends Model
{
    protected $fillable = ['normalizedUrl', 'url', 'depth'];

}
