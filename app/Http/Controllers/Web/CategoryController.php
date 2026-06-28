<?php

namespace App\Http\Controllers\Web;

use App\Application\Categories\Actions\CreateCategory;
use App\Application\Categories\Data\CreateCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class CategoryController extends Controller
{
    public function create(): View
    {
        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request, CreateCategory $createCategory): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $createCategory->execute(new CreateCategoryData(
                name: $validated['name'],
                description: $validated['description'] ?? null,
                isActive: $request->boolean('is_active'),
            ));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'name' => [$exception->getMessage()],
            ]);
        }

        return redirect()
            ->route('categories.create')
            ->with('status', 'Categoria criada com sucesso.');
    }
}
