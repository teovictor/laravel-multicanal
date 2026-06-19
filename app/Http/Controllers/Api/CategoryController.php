<?php

namespace App\Http\Controllers\Api;

use App\Application\Categories\Actions\CreateCategory;
use App\Application\Categories\Data\CreateCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request, CreateCategory $createCategory): JsonResponse
    {
        $validated = $request->validated();

        try {
            $category = $createCategory->execute(new CreateCategoryData(
                name: $validated['name'],
                description: $validated['description'] ?? null,
                isActive: $validated['is_active'] ?? true,
            ));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'name' => [$exception->getMessage()],
            ]);
        }

        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(201);
    }
}
