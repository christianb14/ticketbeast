<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\NotEnoughTicketsException;
use App\Models\Reservation;

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
        return $this->belongsToMany('App\Models\Order', 'tickets');
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

    public function reserveTickets($quantity, $email)
    {
        $tickets = $this->findTickets($quantity)->each(function($ticket){
            $ticket->reserve();
        });

        return new Reservation($tickets, $email);
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
        return Order::ForTickets($email, $tickets, $tickets->sum('price'));
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
