<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;
use App\Models\Ticket;

class TicketTest extends TestCase
{
    use DatabaseMigrations;

    function test_a_ticket_can_be_reserved()
    {
        $ticket = factory(Ticket::class)->create();
        $this->assertNull($ticket->reserved_at);

        $ticket->reserve();

        $this->assertNotNull($ticket->fresh()->reserved_at);
    }

    function test_a_ticket_can_be_release()
    {
        $ticket = factory(Ticket::class)->states('reserved')->create();
        $this->assertNotNull($ticket->reserved_at);

        $ticket->release();
        $this->assertNull($ticket->fresh()->reserved_at);
    }
}
