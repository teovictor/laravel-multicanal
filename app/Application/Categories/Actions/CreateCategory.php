<?php

namespace App\Application\Categories\Actions;

use App\Application\Categories\Data\CreateCategoryData;
use App\Models\Category;
use InvalidArgumentException;

class CreateCategory
{
    public function execute(CreateCategoryData $data): Category
    {
        if (trim($data->name) === '') {
            throw new InvalidArgumentException('Category name is required.');
        }

        return Category::create([
            'name' => $data->name,
            'description' => $data->description,
            'is_active' => $data->isActive,
        ]);
    }
}
