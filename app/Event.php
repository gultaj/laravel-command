<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $casts = [
        'meta' => 'array',
        'schedule' => 'array'
    ];
    protected $fillable = [
        'user_id', 
        'event_place_id', 
        'event_type_id', 
        'title', 
        'description', 
        'startDate', 
        'endDate', 
        'schedule', 
        'price', 
        'meta'
    ];
}
