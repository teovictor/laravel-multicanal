<?php

namespace Tests\Feature\Schedules;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

class DeactivateOutOfStockProductsScheduleTest extends TestCase
{
    public function test_scheduler_contains_the_deactivate_out_of_stock_event(): void
    {
        $this->assertSame(
            'products:deactivate-out-of-stock',
            $this->deactivateOutOfStockEvent()->description,
        );
    }

    public function test_deactivate_out_of_stock_event_runs_daily(): void
    {
        $this->assertSame('0 0 * * *', $this->deactivateOutOfStockEvent()->expression);
    }

    private function deactivateOutOfStockEvent(): Event
    {
        $this->app->make(Kernel::class)->bootstrap();

        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn (Event $event): bool => $event->description === 'products:deactivate-out-of-stock');

        $this->assertNotNull($event, 'The products:deactivate-out-of-stock event was not registered.');

        return $event;
    }
}
