<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CohortAttendance extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = ['scanned_at' => 'datetime'];
}
