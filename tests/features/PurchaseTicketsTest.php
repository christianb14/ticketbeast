<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Concert;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp()
    {
        parent::setUp();
        $this->paymentGateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
    }

    private function orderTickets($concert, $params)
    {
        $savedRequest = $this->app['request'];
        $this->json('POST', "/concerts/{$concert->id}/orders", $params);
        $this->app['request'] = $savedRequest;
    }

    private function assertValidationError($key)
    {
        $this->assertResponseStatus(422);
        $this->assertArrayHasKey($key, $this->decodeResponseJson());
    }

    function test_customer_can_purchase_to_a_published_concert_tickets()
    {
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 3250,
        ])->addTickets(3);

        $this->orderTickets($concert,  [
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertResponseStatus(201);

        $this->seeJsonSubset([
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
            'amount' => 9750,
        ]);

        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        $order = $concert->orders()->where('email', "christian@example.com")->first();
        $this->assertNotNull($order);

        $this->assertTrue($concert->hasOrderFor('christian@example.com'));
        $this->assertEquals(3, $concert->ordersFor('christian@example.com')->first()->ticketsQuantity());
    }

    function test_cannot_purchase_tickets_to_an_unplished_concert()
    {
        $concert = factory(Concert::class)->states('unpublished')->create()->addTickets(3);
        $this->orderTickets($concert, [
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);
        $this->assertResponseStatus(404);
        $this->assertFalse($concert->hasOrderFor('christian@example.com'));
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    function test_an_order_is_not_created_if_payment_fails_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create()->addTickets(3);
        $this->orderTickets($concert,  [
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);

        $this->assertResponseStatus(422);
        $this->assertFalse($concert->hasOrderFor('christian@example.com'));
        $this->assertEquals(3, $concert->ticketsRemaining());
    }

    function test_cannot_purchase_more_tickets_than_remain()
    {
        $concert = factory(Concert::class)->states('published')->create()->addTickets(50);

        $this->orderTickets($concert,  [
            'email' => 'christian@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertResponseStatus(422);
        $this->assertFalse($concert->hasOrderFor('christian@example.com'));
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    function test_cannot_purchase_tickets_another_customer_is_already_trying_to_purchase()
    {
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 1200
        ])->addTickets(3);

        $this->paymentGateway->beforeFirstCharge(function ($paymentGateway) use ($concert) {
            $this->orderTickets($concert,  [
                'email' => 'personB@example.com',
                'ticket_quantity' => 1,
                'payment_token' => $this->paymentGateway->getValidTestToken(),
            ]);

            $this->assertResponseStatus(422);
            $this->assertFalse($concert->hasOrderFor('personB@example.com'));
            $this->assertEquals(0, $this->paymentGateway->totalCharges());
        });

        $this->orderTickets($concert,  [
            'email' => 'personA@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertEquals(3600, $this->paymentGateway->totalCharges());
        $this->assertTrue($concert->hasOrderFor('personA@example.com'));
        $this->assertEquals(3, $concert->ordersFor('personA@example.com')->first()->ticketsQuantity());

    }

    function test_email_is_required_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create()->addTickets(3);
        $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('email');
    }

    function test_email_must_be_valid_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create()->addTickets(3);
        $this->orderTickets($concert, [
            'email' => 'not-an-email-address',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('email');
    }

    function test_ticket_quantity_is_required_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create();
        $concert->addTickets(3);
        $this->orderTickets($concert, [
            'email' => 'christian@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('ticket_quantity');
    }

    function test_ticket_quantity_must_be_at_least_1_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create();
        $concert->addTickets(3);
        $this->orderTickets($concert, [
            'email' => 'christian@example.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('ticket_quantity');
    }


    function test_payment_token_is_required_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $concert->addTickets(3);

        $this->orderTickets($concert, [
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
        ]);

        $this->assertValidationError('payment_token');
    }
}
