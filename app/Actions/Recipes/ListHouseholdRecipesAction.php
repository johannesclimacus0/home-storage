<?php

namespace App\Actions\Recipes;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Recipes\RecipeAvailabilityRepository;
use App\Enums\RecipeAvailabilityFilter;
use App\Models\Recipe;
use App\Services\Recipes\RecipeAvailabilityEvaluator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ListHouseholdRecipesAction
{
    public function __construct(
        private HouseholdRepository $households,
        private RecipeAvailabilityRepository $availability,
        private RecipeAvailabilityEvaluator $evaluator
    ) {}

    public function handle(
        string $householdUuid,
        int $actorUserId,
        RecipeAvailabilityFilter $filter,
        int $perPage
    ): LengthAwarePaginator {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);
        $recipes = $this->availability->paginateForHousehold($household, $filter, $perPage);
        $productIds = new Collection($recipes->items())
            ->flatMap(fn (Recipe $recipe) => $recipe->ingredients->pluck('product_id'))
            ->unique()
            ->values();
        $quantities = $this->availability->quantitiesForProducts($household, $productIds);

        return $recipes->through(
            fn (Recipe $recipe) => $this->evaluator->evaluate($recipe, $quantities)
        );
    }
}
