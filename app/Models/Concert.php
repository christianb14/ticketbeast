<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concert extends Model
{
    protected $guarded = [];

    protected $dates = ['date'];

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function getFormattedDateAttribute()
    {
        return $this->date->format('F j, Y');
    }

    public function getFormattedStartTimeAttribute()
    {
        return $this->date->format('g:ia');
    }

    public function getTicketPriceInDollarsAttribute()
    {
        return number_format($this->ticket_price/100, 2);
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order');
    }

    public function orderTickets($email, $ticket_quantity)
    {
        $order = $this->orders()->create(['email' => $email]);
        
        for ($i=0; $i < $ticket_quantity ; $i++) { 
            $order->tickets()->create([]);
        }

        return $order;
    }
}
