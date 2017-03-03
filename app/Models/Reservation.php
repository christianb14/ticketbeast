<?php

namespace App\Models;

class Reservation
{
    function __construct($tickets, $email)
    {
        $this->tickets = $tickets;
        $this->email = $email;
    }

    public function totalCost()
    {
        return $this->tickets->sum('price');
    }

    public function tickets()
    {
        return $this->tickets;
    }

    public function email()
    {
        return $this->email;
    }

    public function cancel()
    {
        $this->tickets->each(function($ticket){
            $ticket->release();
        });
    }
}