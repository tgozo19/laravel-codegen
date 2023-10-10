<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Models\Traits;

trait AttributesTrait
{
    private array $model_stub_names = [
        'standard' => 'standard',
        'pivot' => 'pivot',
    ];

    private array $should_be_hidden = [
        'password',
        'remember_token',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes'
    ];

    protected array $should_be_casted = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'options' => 'array',
        'settings' => 'collection',
        'birthday' => 'date',
        'deleted_at' => 'datetime',
        'price' => 'decimal:2'
    ];

    protected array $should_be_guarded = [
        'id',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
        'email_verified_at'
    ];

    private array $exceptions = [
        'persons' => 'people',
        'person' => 'people',
    ];
}
