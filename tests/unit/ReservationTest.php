<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;
use App\Models\Reservation;

class ReservationTest extends TestCase
{
    use DatabaseMigrations;

    function test_calculating_the_total_cost()
    {
        $tickets = collect([
            (object) ['price' => 1200],
            (object) ['price' => 1200],
            (object) ['price' => 1200],
        ]);

        $reservation = new Reservation($tickets, 'christian@example.com');

        $this->assertEquals(3600, $reservation->totalCost());
    }

    function test_retrieving_the_reservations_tickets()
    {
        $tickets = collect([
            (object) ['price' => 1200],
            (object) ['price' => 1200],
            (object) ['price' => 1200],
        ]);

        $reservation = new Reservation($tickets, 'christian@example.com');

        $this->assertEquals($tickets, $reservation->tickets());
    }

    function test_retrieving_the_customers_email()
    {
        $tickets = collect();

        $reservation = new Reservation($tickets, 'christian@example.com');

        $this->assertEquals('christian@example.com', $reservation->email());
    }

    function test_reserverd_tickets_are_released_when_a_reservation_is_cancelled()
    {
        $tickets = collect([
            Mockery::spy(Ticket::class),
            Mockery::spy(Ticket::class),
            Mockery::spy(Ticket::class),
        ]);

        $reservation = new Reservation($tickets, 'christian@example.com');

        $reservation->cancel();

        $tickets->each(function($ticket){
            $ticket->shouldHaveReceived('release');
        });
    }
}