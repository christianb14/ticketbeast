<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;

class TicketTest extends TestCase
{
    use DatabaseMigrations;

    function test_a_ticket_can_be_release()
    {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(1);
        $order = $concert->orderTickets('christian@example.com', 1);
        $ticket = $order->tickets()->first();

        $this->assertEquals($order->id, $ticket->order_id);
        $ticket->release();

        $this->assertNull($ticket->fresh()->order_id);
    }
}
