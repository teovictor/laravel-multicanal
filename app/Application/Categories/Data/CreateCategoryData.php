<?php

namespace App\Application\Categories\Data;

final readonly class CreateCategoryData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $isActive = true,
    ) {
    }
}
