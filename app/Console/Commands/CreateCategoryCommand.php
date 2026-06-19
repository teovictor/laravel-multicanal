<?php

namespace App\Console\Commands;

use App\Application\Categories\Actions\CreateCategory;
use App\Application\Categories\Data\CreateCategoryData;
use Illuminate\Console\Command;
use InvalidArgumentException;

class CreateCategoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'category:create
        {--name= : Category name}
        {--description= : Category description}
        {--inactive : Create the category as inactive}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a category';

    /**
     * Execute the console command.
     */
    public function handle(CreateCategory $createCategory): int
    {
        $name = $this->option('name');
        $description = $this->option('description');
        $isActive = ! $this->option('inactive');

        if ($name === null) {
            if (! $this->input->isInteractive()) {
                $this->error('The --name option is required when running non-interactively.');

                return self::FAILURE;
            }

            $name = $this->ask('Category name');
            $description = $description ?? $this->ask('Category description');

            if (! $this->option('inactive')) {
                $isActive = $this->confirm('Create as active?', true);
            }
        }

        $name = (string) $name;

        if ($description === '') {
            $description = null;
        }

        try {
            $category = $createCategory->execute(new CreateCategoryData(
                name: $name,
                description: $description,
                isActive: $isActive,
            ));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Category created with ID {$category->id}: {$category->name}");

        return self::SUCCESS;
    }
}
