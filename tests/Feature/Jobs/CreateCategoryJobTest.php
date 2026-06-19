<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CreateCategoryJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class CreateCategoryJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        CreateCategoryJob::dispatch(
            name: 'Office Supplies',
            description: 'Products used in office routines.',
        );

        Queue::assertPushed(CreateCategoryJob::class, function (CreateCategoryJob $job): bool {
            return $job->name === 'Office Supplies'
                && $job->description === 'Products used in office routines.'
                && $job->isActive === true;
        });
    }

    public function test_job_creates_an_active_category_by_default(): void
    {
        CreateCategoryJob::dispatchSync(
            name: 'Office Supplies',
            description: 'Products used in office routines.',
        );

        $this->assertDatabaseHas('categories', [
            'name' => 'Office Supplies',
            'description' => 'Products used in office routines.',
            'is_active' => true,
        ]);
    }

    public function test_job_creates_an_inactive_category(): void
    {
        CreateCategoryJob::dispatchSync(
            name: 'Archived Items',
            description: 'Items no longer available.',
            isActive: false,
        );

        $this->assertDatabaseHas('categories', [
            'name' => 'Archived Items',
            'description' => 'Items no longer available.',
            'is_active' => false,
        ]);
    }

    public function test_job_creates_a_category_without_description(): void
    {
        CreateCategoryJob::dispatchSync(name: 'Books');

        $this->assertDatabaseHas('categories', [
            'name' => 'Books',
            'description' => null,
            'is_active' => true,
        ]);
    }

    public function test_job_throws_exception_for_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name is required.');

        try {
            CreateCategoryJob::dispatchSync(name: '');
        } finally {
            $this->assertDatabaseCount('categories', 0);
        }
    }

    public function test_job_throws_exception_for_blank_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Category name is required.');

        try {
            CreateCategoryJob::dispatchSync(name: '   ');
        } finally {
            $this->assertDatabaseCount('categories', 0);
        }
    }

    public function test_job_implements_should_queue(): void
    {
        $job = new CreateCategoryJob(name: 'Office Supplies');

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }
}
