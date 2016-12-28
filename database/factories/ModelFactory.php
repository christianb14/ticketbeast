<?php

use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Models\Concert::class, function (Faker\Generator $faker) {

    return [
            'title' => 'New Title',
            'date' => Carbon::parse('+2 weeks'),
            'subtitle' => 'Another Subtitle',
            'ticket_price' => '3500',
            'venue' => 'The principal avenue',
            'venue_address' => '123 example',
            'city' => 'Example city',
            'state' => 'CI',
            'zip' => '32149',
            'additional_information' => 'Some additional information'
    ];
});

$factory->state(App\Models\Concert::class, 'published', function ($faker){

    return [
        'published_at' => Carbon::parse('-1 week'),
    ];  
});

$factory->state(App\Models\Concert::class, 'unpublished', function ($faker){

    return [
        'published_at' => null,
    ];  
});