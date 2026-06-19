<?php

namespace App\Jobs;

use App\Application\Categories\Actions\CreateCategory;
use App\Application\Categories\Data\CreateCategoryData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateCategoryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $isActive = true,
    ) {
    }

    public function handle(CreateCategory $createCategory): void
    {
        $createCategory->execute(new CreateCategoryData(
            name: $this->name,
            description: $this->description,
            isActive: $this->isActive,
        ));
    }
}
