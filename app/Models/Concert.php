<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\NotEnoughTicketsException;

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

    public function hasOrderFor($customerEmail)
    {
        return $this->orders()->where('email', $customerEmail)->count() > 0;
    }

    public function ordersFor($customerEmail)
    {
        return $this->orders()->where('email', $customerEmail)->get();
    }

    public function tickets()
    {
        return $this->hasMany('App\Models\Ticket');
    }

    public function orderTickets($email, $ticket_quantity)
    {
        $tickets = $this->findTickets($ticket_quantity);

        return $this->createOrder($email, $tickets);
    }

    public function findTickets($ticket_quantity)
    {
        $tickets = $this->tickets()->available()->take($ticket_quantity)->get();
        
        if($tickets->count() < $ticket_quantity) {
            throw new NotEnoughTicketsException;
        }

        return $tickets;
    }

    public function createOrder($email, $tickets)
    {
        $order = $this->orders()->create([
            'email' => $email,
            'amount' => $tickets->count()*$this->ticket_price
        ]);

        foreach ($tickets as $ticket) { 
            $order->tickets()->save($ticket);
        }

        return $order;
    }

    public function addTickets($quantity)
    {
        for ($i=0; $i < $quantity ; $i++) { 
            $this->tickets()->create([]);
        }

        return $this;
    }

    public function ticketsRemaining()
    {
        return $this->tickets()->available()->count();
    }
}
