<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;
use App\Models\Order;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    function test_creating_an_order_from_tickets_email()
    {
        $concert = factory(Concert::class)->create(['ticket_price' => 1200])->addTickets(5);
        $this->assertEquals(5, $concert->ticketsRemaining());

        $order = Order::ForTickets('christian@example.com', $concert->findTickets(3), 3600);

        $this->assertEquals('christian@example.com', $order->email);
        $this->assertEquals(3, $order->ticketsQuantity());
        $this->assertEquals(3600, $order->amount);
        $this->assertEquals(2, $concert->ticketsRemaining());
    }

    public function test_converting_to_an_array()
    {
        $concert = factory(Concert::class)->create(['ticket_price' => 1200])->addTickets(5);
        $order = $concert->orderTickets('christian@example.com', 5);

        $result = $order->toArray();

        $this->assertEquals([
            'email' => 'christian@example.com',
            'ticket_quantity' => 5,
            'amount' => 6000,
        ], $result);
    }
}
