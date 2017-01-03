<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;
use App\Models\Order;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

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

    public function test_tickets_are_released_when_order_is_cancelled()
    {

        $concert = factory(Concert::class)->create()->addTickets(10);
        $order = $concert->orderTickets('christian@example.com', 5);

        $this->assertEquals(5, $concert->ticketsRemaining());

        $order->cancel();

        $this->assertEquals(10, $concert->ticketsRemaining());
        $this->assertNull(Order::find($order->id));
    }
}
