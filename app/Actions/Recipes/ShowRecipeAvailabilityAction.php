<?php

namespace App\Actions\Recipes;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Recipes\RecipeAvailabilityRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\RecipeAvailabilityData;
use App\Services\Recipes\RecipeAvailabilityEvaluator;

final readonly class ShowRecipeAvailabilityAction
{
    public function __construct(
        private HouseholdRepository $households,
        private RecipeRepository $recipes,
        private RecipeAvailabilityRepository $availability,
        private RecipeAvailabilityEvaluator $evaluator
    ) {}

    public function handle(
        string $householdUuid,
        int $actorUserId,
        string $recipeUuid
    ): RecipeAvailabilityData {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);
        $recipe = $this->recipes->findByUuid($recipeUuid);
        $productIds = $recipe->ingredients->pluck('product_id')->unique()->values();
        $quantities = $this->availability->quantitiesForProducts($household, $productIds);

        return $this->evaluator->evaluate($recipe, $quantities);
    }
}
