<?php

namespace Tgozo\LaravelCodegen\Tests\Feature;

use Illuminate\Support\Facades\File;
use Tgozo\LaravelCodegen\Tests\TestCase;

uses(TestCase::class);

it('generates event and listener when --events is passed', function () {
    $eventPath = app_path('Events/EventTicketCreated.php');
    $listenerPath = app_path('Listeners/HandleEventTicketCreated.php');

    if (File::exists($eventPath)) File::delete($eventPath);
    if (File::exists($listenerPath)) File::delete($listenerPath);

    $this->artisan('make:codegen-migration', [
        'name' => 'create_event_tickets_table',
        '--events' => true,
        '--force' => true,
        '-n' => true,
    ])->assertExitCode(0);

    expect(File::exists($eventPath))->toBeTrue();
    expect(File::exists($listenerPath))->toBeTrue();

    // Cleanup
    File::delete($eventPath);
    File::delete($listenerPath);
});
