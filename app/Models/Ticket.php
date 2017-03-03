<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ticket extends Model
{
    protected $guarded = [];

    public function scopeAvailable($query)
    {
        return $query->whereNull('order_id')->whereNull('reserved_at');
    }

    public function reserve()
    {
        $this->update(['reserved_at' => Carbon::now()]);
    }

    public function release()
    {
        $this->update(['reserved_at' => null]);
    }

    public function concert()
    {
        return $this->belongsTo('App\Models\Concert');
    }

    public function getPriceAttribute()
    {
        return $this->concert->ticket_price;
    }
}
