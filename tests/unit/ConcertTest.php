<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;
use Carbon\Carbon;

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
}
