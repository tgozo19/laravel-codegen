<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Tgozo\LaravelCodegen\Console\FakerGuesser;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('guesses smart faker methods based on column names and types', function () {
    expect(FakerGuesser::fetch_guess('avatar', 'string'))->toBe('fake()->imageUrl()');
    expect(FakerGuesser::fetch_guess('phone', 'string'))->toBe('fake()->phoneNumber');
    expect(FakerGuesser::fetch_guess('website', 'string'))->toBe('fake()->url');
    expect(FakerGuesser::fetch_guess('is_active', 'boolean'))->toBe('fake()->boolean()');
    expect(FakerGuesser::fetch_guess('price', 'decimal'))->toBe('fake()->unique()->randomFloat(2, 10, 500)');
    expect(FakerGuesser::fetch_guess('ip', 'string'))->toBe('fake()->ipv4');
});
