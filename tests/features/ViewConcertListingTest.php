<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;
use Carbon\Carbon;

class ViewConcertListingTest extends TestCase
{
    use DatabaseMigrations;

    function test_user_can_view_a_published_concert_listing()
    {
        $concert = factory(Concert::class)->states('published')->create([
            'title' => 'The Red Chort',
            'subtitle' => 'in the Stadium',
            'date'  => Carbon::parse('December 13, 2016 8:00pm'),
            'ticket_price' => '1234',
            'venue' => 'The Mosh Pit',
            'venue_address' => '123 example',
            'city' => 'Laraville',
            'state' => 'ON',
            'zip' => '12346',
            'additional_information' => 'For tickets, call to 555-555',
        ]);


        //Act
        //View to concert listing

        $this->visit('/concerts/'.$concert->id);

        //Assert
        //See the concert details

        $this->see('The Red Chort');
        $this->see('in the Stadium');
        $this->see('December 13, 2016');
        $this->see('Doors at 8:00pm');
        $this->see('12.34');
        $this->see('The Mosh Pit');
        $this->see('123 example');
        $this->see('Laraville, ON 12346');
        $this->see('For tickets, call to 555-555');
    }

    public function test_user_cannot_view_unpublished_concert_listings()
    {
        $concert = factory(Concert::class)->states('unpublished')->create();

        $this->get('/concerts/'.$concert->id);

        //Assert
        //See the concert details

        $this->assertResponseStatus(404);
    }
}
