<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public static function ForTickets($email, $tickets, $amount)
    {
        $order = self::create([
            'email' => $email,
            'amount' => $amount
        ]);

        foreach ($tickets as $ticket) { 
            $order->tickets()->save($ticket);
        }

        return $order;
    } 

    public function concert()
    {
        return $this->belongsTo('App\Models\Concert');
    }

    public function tickets()
    {
        return $this->hasMany('App\Models\Ticket');
    }

    public function ticketsQuantity()
    {
        return $this->tickets()->count();
    }

    public function toArray()
    {
        return [
            'email' => $this->email,
            'ticket_quantity' => $this->ticketsQuantity(),
            'amount' => $this->amount
        ];
    }
}
