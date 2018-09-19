<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CalendarRecurringDay extends Model
{

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'repeat_day','repeat_event_id'
    ];

}
