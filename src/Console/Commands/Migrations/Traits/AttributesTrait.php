<?php

namespace Tgozo\LaravelCodegen\Console\Commands\Migrations\Traits;

trait AttributesTrait
{
    private array $types = [
        'string', 'text', 'integer', 'bigInteger', 'unsignedBigInteger', 'mediumInteger', 'tinyInteger', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger', 'decimal', 'unsignedDecimal', 'float', 'double', 'boolean', 'enum', 'json', 'jsonb', 'date', 'dateTime', 'dateTimeTz', 'time', 'timeTz', 'timestamp', 'timestampTz', 'year', 'binary', 'uuid', 'ipAddress', 'macAddress'
    ];
    private array $numberTypes = [
        'integer', 'bigInteger', 'mediumInteger', 'tinyInteger', 'unsignedInteger', 'unsignedMediumInteger', 'unsignedSmallInteger', 'unsignedTinyInteger'
    ];

    private array $patterns = [
        'start' => ['create_', 'add_column_to_', 'add_columns_to_'],
        'ending' => [
            'create_' => ['_table'],
            'add_column_to_' => ['_table'],
            'add_columns_to_' => ['_table'],
        ]
    ];

    private array $valid_options = [
        'nullable', 'default', 'unique', 'after', 'charset', 'collation', 'comment', 'first',
        'storedAs', 'unsigned', 'useCurrent', 'useCurrentOnUpdate', 'virtualAs', 'autoIncrement', 'softDeletes'
    ];

    private array $option_actions = [
        'nullable' => true,
        'default' => 'take_value',
        'unique' => true,
        'after' => 'take_value',
        'charset' => 'take_value',
        'collation' => 'take_value',
        'comment' => 'take_value',
        'first' => true,
        'storedAs' => 'take_value',
        'unsigned' => true,
        'useCurrent' => true,
        'useCurrentOnUpdate' => true,
        'virtualAs' => 'take_value',
        'autoIncrement' => 'take_value',
        'softDeletes' => true
    ];

    private array $incompatible_options = [
        'nullable' => ['default', 'unique', 'index', 'primary_key', 'autoIncrement'],
        'default' => ['nullable', 'unique', 'autoIncrement'],
        'primary_key' => ['nullable', 'default'],
        'unique' => ['nullable', 'default'],
        'index' => ['nullable', 'default'],
        'after' => ['first'],
        'first' => ['after'],
    ];

    private array $stub_names = [
        'create_' => 'create',
        'add_column_to_' => 'update',
        'add_columns_to_' => 'update',
    ];

    public array $common_columns = [
        'id' => ['bigIncrements', 'unsignedBigInteger', 'increments', 'unsignedInteger'],
        'name' => ['string'],
        'first_name' => ['string'],
        'last_name' => ['string'],
        'description' => ['string'],
        'body' => ['text'],
        'title' => ['string'],
        'subject' => ['string'],
        'message' => ['text'],
        'slug' => ['string'],
        'url' => ['string'],
        'link' => ['string'],
        'image' => ['string'],
        'content' => ['text'],
        'logo' => ['string'],
        'status' => ['string'],
        'type' => ['string'],
        'mime_type' => ['string'],
        'order' => ['integer'],
        'cost' => ['decimal'],
        'value' => ['decimal'],
        'price' => ['decimal'],
        'discount' => ['decimal'],
        'qty' => ['integer'],
        'quantity' => ['integer'],
        'parent_id' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'parent' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'child_id' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'child' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'user' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'user_id' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'created_by' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'updated_by' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'deleted_by' => ['bigInteger', 'unsignedBigInteger', 'integer', 'unsignedInteger'],
        'email' => ['string'],
        'password' => ['string'],
        'remember_token' => ['string'],
        'created_at' => ['timestamp', 'timestampTz', 'dateTime', 'dateTimeTz'],
        'updated_at' => ['timestamp', 'timestampTz', 'dateTime', 'dateTimeTz'],
        'deleted_at' => ['timestamp', 'timestampTz', 'dateTime', 'dateTimeTz'],
    ];

    private array $modifiers_incompatible_types = [
        'autoIncrement' => ['string', 'char', 'date', 'dateTime', 'dateTimeTz', 'time', 'timeTz', 'timestamp', 'timestampTz', 'text', 'mediumText', 'longText', 'json', 'jsonb', 'binary', 'uuid'],
        'charset' => ['integer', 'bigInteger', 'mediumInteger', 'smallInteger', 'tinyInteger', 'float', 'double', 'decimal'],
        'collation' => ['integer', 'bigInteger', 'mediumInteger', 'smallInteger', 'tinyInteger', 'float', 'double', 'decimal'],
        'default' => [],
        'nullable' => [],
        'unsigned' => ['char','date','dateTime','dateTimeTz','time','timeTz','timestamp','timestampTz','text','mediumText','longText','json','jsonb','binary','uuid'],
        'useCurrent' => ['char','integer','bigInteger','mediumInteger','smallInteger','tinyInteger','float','double','decimal','text','mediumText','longText','json','jsonb','binary','uuid']
    ];

    private array $modifiers_incompatible_command_types = [
        'after' => ['create_'],
    ];

    protected ?array $option_exceptions;

}
