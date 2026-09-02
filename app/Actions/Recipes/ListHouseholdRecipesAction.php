<?php

namespace App\Actions\Recipes;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Recipes\RecipeAvailabilityRepository;
use App\DTO\Recipes\RecipeAvailabilityData;
use App\Enums\RecipeAvailabilityFilter;
use App\Models\Recipe;
use App\Services\Recipes\RecipeAvailabilityEvaluator;
use App\Support\Cache\RecipeCache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ListHouseholdRecipesAction
{
    public function __construct(
        private HouseholdRepository $households,
        private RecipeAvailabilityRepository $availability,
        private RecipeAvailabilityEvaluator $evaluator,
        private RecipeCache $cache
    ) {}

    public function handle(
        string $householdUuid,
        int $actorUserId,
        RecipeAvailabilityFilter $filter,
        int $perPage
    ): LengthAwarePaginator {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return $this->cache->rememberHouseholdList(
            $householdUuid,
            $filter,
            $perPage,
            $page,
            function () use ($household, $filter, $perPage): LengthAwarePaginator {
                $recipes = $this->availability->paginateForHousehold($household, $filter, $perPage);
                $productIds = new Collection($recipes->items())
                    ->flatMap(fn (Recipe $recipe) => $recipe->ingredients->pluck('product_id'))
                    ->unique()
                    ->values();
                $quantities = $this->availability->quantitiesForProducts($household, $productIds);

                return $recipes->through(
                    fn (Recipe $recipe): array => $this->toListArray(
                        $this->evaluator->evaluate($recipe, $quantities)
                    )
                );
            }
        );
    }

    /** @return array<string, mixed> */
    private function toListArray(RecipeAvailabilityData $availability): array
    {
        $recipe = $availability->recipe;

        return [
            'uuid' => $recipe->uuid,
            'title' => $recipe->title,
            'description' => $recipe->description,
            'servings' => $recipe->servings,
            'before_cooking_minutes' => $recipe->before_cooking_minutes,
            'cooking_minutes' => $recipe->cooking_minutes,
            'creator' => $recipe->creator === null ? null : [
                'id' => $recipe->creator->getKey(),
                'name' => $recipe->creator->name,
            ],
            'ingredients_count' => $recipe->ingredients_count,
            'steps_count' => $recipe->steps_count,
            'availability' => [
                'can_make' => $availability->canMake,
                'missing_required_count' => $availability->missingRequiredCount,
            ],
        ];
    }
}
