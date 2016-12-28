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
        $this->json('POST', "/concerts/{$concert->id}/orders", $params);
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
        ]);

        $this->orderTickets($concert,  [
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertResponseStatus(201);

        //Make sure the customer was charged the correct amount
        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        //Make sure that an order exist for this customer

        $order = $concert->orders()->where('email', "christian@example.com")->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets()->count());
    }

    function test_cannot_purchase_tickets_to_an_unplished_concert()
    {
        $concert = factory(Concert::class)->states('unpublished')->create();

        $this->orderTickets($concert, [
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertResponseStatus(404);
        $this->assertEquals(0, $concert->orders()->count());
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    function test_email_is_required_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('email');
    }

    function test_email_must_be_valid_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create();

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

        $this->orderTickets($concert, [
            'email' => 'christian@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $this->assertValidationError('ticket_quantity');
    }

    function test_ticket_quantity_must_be_at_least_1_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create();

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

        $this->orderTickets($concert, [
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
        ]);

        $this->assertValidationError('payment_token');
    }

    function test_an_order_is_not_created_if_payment_fails_to_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->states('published')->create();

        $this->orderTickets($concert,  [
            'email' => 'christian@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);

        $this->assertResponseStatus(422);
        $order = $concert->orders()->where('email', "christian@example.com")->first();
        $this->assertNull($order);

    }
}
