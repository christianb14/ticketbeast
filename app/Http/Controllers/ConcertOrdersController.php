<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Billing\PaymentGateway;
use App\Billing\PaymentFailedException;
use App\Exceptions\NotEnoughTicketsException;
use App\Models\Concert;
use App\Models\Order;
use App\Models\Reservation;

class ConcertOrdersController extends Controller
{
    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($concert_id)
    {
        $concert = Concert::published()->findOrFail($concert_id);
        $this->validate(request(), [
            'email' => 'required|email',
            'ticket_quantity' => 'required|integer|min:1',
            'payment_token' => 'required'
        ]);

        try {
            //Find some tickets
            $tickets = $concert->findTickets(request('ticket_quantity'));

            $reservation = new Reservation($tickets);
            //Charge the customer for the tickets
            $this->paymentGateway->charge($reservation->totalCost(), request('payment_token'));

            //Create an order for those tickets
            $order = Order::ForTickets(request('email'), $tickets, $reservation->totalCost());

            return response()->json($order, 201);
        } catch (PaymentFailedException $e) {

            return response()->json([], 422);
        } catch (NotEnoughTicketsException $e) {

            return response()->json([], 422);
        }
    }
}
