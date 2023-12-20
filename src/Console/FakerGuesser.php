<?php

namespace Tgozo\LaravelCodegen\Console;

use Illuminate\Support\Facades\Hash;

class FakerGuesser
{

    public static array $common_returns = [
        'name' => 'fake()->unique()->name',
        'first_name' => 'fake()->unique()->firstName',
        'last_name' => 'fake()->unique()->lastName',
        'description' => 'fake()->unique()->text',
        'body' => 'fake()->unique()->paragraph',
        'title' => 'fake()->unique()->title',
        'subject' => 'fake()->unique()->text(20)',
        'message' => 'fake()->unique()->paragraph',
        'cost' => 'fake()->unique()->randomNumber(4)',
        'value' => 'fake()->unique()->randomNumber(4)',
        'price' => 'fake()->unique()->randomNumber(4)',
        'discount' => 'fake()->unique()->randomNumber(4)',
        'qty' => 'fake()->unique()->randomNumber()',
        'quantity' => 'fake()->unique()->randomNumber()',
        'user' => 'fake()->unique()->randomNumber()',
        'email' => 'fake()->unique()->safeEmail',
	    'phone_number' => 'fake()->unique()->phoneNumber',
        'password' => 'fake()->unique()->password',
        'user_id' => 'fake()->unique()->randomNumber()',
        'created_at' => 'now()',
        'updated_at' => 'now()',
        'deleted_at' => 'now()',
        'email_verified_at' => 'now()',
        'created_by' => 'fake()->unique()->randomNumber()',
        'updated_by' => 'fake()->unique()->randomNumber()',
        'deleted_by' => 'fake()->unique()->randomNumber()',
        'country' => 'fake()->unique()->country',
        'city' => 'fake()->unique()->city',
        'currencyCode' => 'fake()->unique()->currencyCode',
        'currency_code' => 'fake()->unique()->currencyCode',
        'address' => 'fake()->unique()->address'
    ];

    public static function guess($column_name, $column_type = "string"): array
    {
        $guess = self::fetch_guess($column_name, $column_type);
        if ($column_name === 'password' or str($column_name)->contains('password')){
            $initial_value = fake()->unique()->password;
            $guess = Hash::make($initial_value);
        }
        return [$guess, $initial_value ?? ''];
    }

    public static function fetch_guess($column_name, mixed $column_type)
    {
        if (key_exists($column_name, self::$common_returns)) {
            return self::$common_returns[$column_name];
        }

        if (str($column_name)->endsWith('at')) {
            return 'now()';
        }

        if (str($column_name)->endsWith('by')) {
            return 'fake()->unique()->randomNumber()';
        }

        if (str($column_name)->contains('password')) {
            return 'fake()->unique()->password';
        }

        if (str($column_name)->contains('token')) {
            return '\Str::random(10)';
        }

	    if (str($column_name)->contains('phone_number')) {
            return 'fake()->unique()->phoneNumber';
        }

        if (str($column_name)->endsWith('_id')) {
            return 'fake()->unique()->randomNumber()';
        }

        if ($column_type === 'integer') {
            return 'fake()->randomNumber()';
        }

        return 'fake()->text';
    }
}
