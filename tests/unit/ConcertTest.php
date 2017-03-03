<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;
use Carbon\Carbon;
use App\Exceptions\NotEnoughTicketsException;

class ConcertTest extends TestCase
{
    use DatabaseMigrations;
    
    function test_can_get_formatted_date()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2016-12-01 8:00pm'),
        ]);

        $this->assertEquals('December 1, 2016',$concert->formatted_date);
    }

    function test_can_get_formatted_start_time()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2016-02-01 20:00:00'),
        ]);

        $this->assertEquals('8:00pm', $concert->formatted_start_time);
    }

    function test_can_get_ticket_price_in_dollars()
    {
        $concert = factory(Concert::class)->make([
            'ticket_price' => 1234,
        ]);

        $this->assertEquals('12.34', $concert->ticket_price_in_dollars);
    }

    function test_concerts_with_a_published_at_date_are_published()
    {
        $publishedConcertA = factory(Concert::class)->create(['published_at' => Carbon::parse('-1 week')]);
        $publishedConcertB = factory(Concert::class)->create(['published_at' => Carbon::parse('-1 week')]);
        $unpublishedConcert = factory(Concert::class)->create(['published_at' => null]);

        $publishedConcerts = Concert::published()->get();

        $this->assertTrue($publishedConcerts->contains($publishedConcertA));
        $this->assertTrue($publishedConcerts->contains($publishedConcertB));
        $this->assertFalse($publishedConcerts->contains($unpublishedConcert));
    }

    function test_can_order_concert_tickets()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);

        $order = $concert->orderTickets('christian@example.com', 3);

        $this->assertEquals('christian@example.com', $order->email);
        $this->assertEquals(3, $order->ticketsQuantity());
    }

    function test_can_add_tickets()
    {
        $concert = factory(Concert::class)->create();
        $concert->addTickets(50);

        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    function test_tickets_remaining_does_not_include_tickets_associated_with_an_order()
    {
        $concert = factory(Concert::class)->create()->addTickets(50);
        $order = $concert->orderTickets('christian@example.com', 30);

        $this->assertEquals(20, $concert->ticketsRemaining());
    }

    function test_trying_to_purchase_more_tickets_than_remain_throws_an_exception()
    {
        $concert = factory(Concert::class)->create()->addTickets(10);

        try {
            $concert->orderTickets('christian@example.com', 11);
        } catch (NotEnoughTicketsException $e) {
            $this->assertFalse($concert->hasOrderFor('christian@example.com'));
            $this->assertEquals(10, $concert->ticketsRemaining());

            return;
        }

        $this->fail("Order successed event there wasn't enough tickets");
    }

    function test_cannot_order_tickets_that_have_already_been_purchased()
    {
        $concert = factory(Concert::class)->create()->addTickets(10);
        $concert->orderTickets('test@test.com', 8);

        try {
            $concert->orderTickets('christian@example.com', 3);
        } catch (NotEnoughTicketsException $e) {
            $this->assertFalse($concert->hasOrderFor('christian@example.com'));
            $this->assertEquals(2, $concert->ticketsRemaining());

            return;
        }

        $this->fail("Order successed even there wasn't enough tickets");
    }

    function test_can_reserve_avaiable_tickets()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);
        $this->assertEquals(3, $concert->ticketsRemaining());

        $reservation = $concert->reserveTickets(2, 'christian@example.com');

        $this->assertCount(2, $reservation->tickets());
        $this->assertEquals('christian@example.com', $reservation->email());
        $this->assertEquals(1, $concert->ticketsRemaining());
    }

    function test_cannot_reserve_tickets_that_have_already_been_purchased()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);
        $concert->reserveTickets(2, 'christian@example.com');

        try {
            $concert->reserveTickets(2, 'christian@example.com');
        } catch (NotEnoughTicketsException $e) {
            $this->assertEquals(1, $concert->ticketsRemaining());

            return;
        }

        $this->fail("Reserving tickets successed even though the tickets were already sold");
    }

    function test_cannot_reserve_tickets_that_have_already_been_reserved()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);
        $concert->reserveTickets(2, 'christian@example.com');

        try {
            $concert->reserveTickets(2, 'christian@example.com');
        } catch (NotEnoughTicketsException $e) {
            $this->assertEquals(1, $concert->ticketsRemaining());
            return;
        }

        $this->fail("Reserving tickets successed even though the tickets were already reserved");
    }
}
