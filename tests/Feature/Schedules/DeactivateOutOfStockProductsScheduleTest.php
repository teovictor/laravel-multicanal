<?php

namespace Tests\Feature\Schedules;

use App\Models\Product;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeactivateOutOfStockProductsScheduleTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_schedule_test_reports_success_when_two_products_are_deactivated(): void
    {
        Product::factory()->count(2)->outOfStock()->create([
            'is_active' => true,
        ]);

        Artisan::call('schedule:test', [
            '--name' => 'products:deactivate-out-of-stock',
            '--no-interaction' => true,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('DONE', $output);
        $this->assertStringNotContainsString('FAIL', $output);
        $this->assertDatabaseMissing('products', [
            'stock' => 0,
            'is_active' => true,
        ]);
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
