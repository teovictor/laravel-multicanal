<?php

namespace Tests\Feature\Schedules;

use App\Application\Products\Actions\DeactivateOutOfStockProducts;
use App\Schedules\DeactivateOutOfStockProductsTask;
use Tests\TestCase;

class DeactivateOutOfStockProductsTaskTest extends TestCase
{
    public function test_it_delegates_execution_to_the_action(): void
    {
        $this->mock(DeactivateOutOfStockProducts::class, function ($mock): void {
            $mock->shouldReceive('execute')
                ->once()
                ->withNoArgs()
                ->andReturn(3);
        });

        $result = $this->app
            ->make(DeactivateOutOfStockProductsTask::class)();

        $this->assertNull($result);
    }
}
