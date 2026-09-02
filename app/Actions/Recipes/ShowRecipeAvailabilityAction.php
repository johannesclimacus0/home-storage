<?php

namespace App\Actions\Recipes;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Recipes\RecipeAvailabilityRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\RecipeAvailabilityData;
use App\DTO\Recipes\RecipeIngredientAvailabilityData;
use App\Services\Recipes\RecipeAvailabilityEvaluator;
use App\Support\Cache\RecipeCache;

final readonly class ShowRecipeAvailabilityAction
{
    public function __construct(
        private HouseholdRepository $households,
        private RecipeRepository $recipes,
        private RecipeAvailabilityRepository $availability,
        private RecipeAvailabilityEvaluator $evaluator,
        private RecipeCache $cache
    ) {}

    public function handle(
        string $householdUuid,
        int $actorUserId,
        string $recipeUuid
    ): array {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->cache->rememberAvailability(
            $householdUuid,
            $recipeUuid,
            function () use ($household, $recipeUuid): array {
                $recipe = $this->recipes->findByUuid($recipeUuid);
                $productIds = $recipe->ingredients->pluck('product_id')->unique()->values();
                $quantities = $this->availability->quantitiesForProducts($household, $productIds);

                return $this->toArray($this->evaluator->evaluate($recipe, $quantities));
            }
        );
    }

    /** @return array<string, mixed> */
    private function toArray(RecipeAvailabilityData $availability): array
    {
        return [
            'recipe_uuid' => $availability->recipe->uuid,
            'can_make' => $availability->canMake,
            'missing_required_count' => $availability->missingRequiredCount,
            'ingredients' => $availability->ingredients->map(
                fn (RecipeIngredientAvailabilityData $ingredient): array => [
                    'ingredient_uuid' => $ingredient->ingredient->uuid,
                    'product' => [
                        'uuid' => $ingredient->ingredient->product->uuid,
                        'name' => $ingredient->ingredient->product->name,
                        'measurement_type' => $ingredient->ingredient->product->measurement_type->value,
                    ],
                    'required_quantity' => $ingredient->ingredient->quantity,
                    'available_quantity' => $ingredient->availableQuantity,
                    'missing_quantity' => $ingredient->missingQuantity,
                    'is_optional' => $ingredient->ingredient->is_optional,
                    'sufficient' => $ingredient->sufficient,
                ]
            )->values()->all(),
        ];
    }
}
