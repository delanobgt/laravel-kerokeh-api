<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    //
    protected $fillable = [
        'title', 'duration', 'accompaniment_path', 'vocals_path'
    ];
}
