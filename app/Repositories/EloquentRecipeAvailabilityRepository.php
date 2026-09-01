<?php

namespace App\Repositories;

use App\Contracts\Recipes\RecipeAvailabilityRepository;
use App\Enums\RecipeAvailabilityFilter;
use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class EloquentRecipeAvailabilityRepository implements RecipeAvailabilityRepository
{
    public function paginateForHousehold(
        Household $household,
        RecipeAvailabilityFilter $filter,
        int $perPage
    ): LengthAwarePaginator {
        $query = Recipe::query()
            ->with(['creator', 'ingredients.product'])
            ->withCount(['ingredients', 'steps'])
            ->orderBy('title')
            ->orderBy('id');

        if ($filter !== RecipeAvailabilityFilter::All) {
            $this->applyAvailabilityFilter($query, $household, $filter);
        }

        return $query->paginate($perPage);
    }

    public function quantitiesForProducts(
        Household $household,
        Collection $productIds
    ): array {
        if ($productIds->isEmpty()) {
            return [];
        }

        return HouseholdProduct::query()
            ->leftJoin('stocks', 'stocks.household_product_id', '=', 'household_products.id')
            ->where('household_products.household_id', $household->getKey())
            ->whereIn('household_products.product_id', $productIds->unique()->all())
            ->groupBy('household_products.product_id')
            ->select('household_products.product_id')
            ->selectRaw('COALESCE(SUM(stocks.quantity), 0) AS total_quantity')
            ->pluck('total_quantity', 'product_id')
            ->map(fn ($quantity) => (string) $quantity)
            ->all();
    }

    private function applyAvailabilityFilter(
        Builder $query,
        Household $household,
        RecipeAvailabilityFilter $filter
    ): void {
        $method = $filter === RecipeAvailabilityFilter::Available
            ? 'whereNotExists'
            : 'whereExists';

        $query->{$method}(function (QueryBuilder $ingredients) use ($household): void {
            $ingredients
                ->selectRaw('1')
                ->from('recipe_ingredients')
                ->whereColumn('recipe_ingredients.recipe_id', 'recipes.id')
                ->where('recipe_ingredients.is_optional', false)
                ->whereRaw(
                    <<<'SQL'
                    COALESCE((
                        SELECT SUM(stocks.quantity)
                        FROM household_products
                        JOIN stocks ON stocks.household_product_id = household_products.id
                        WHERE household_products.household_id = ?
                          AND household_products.product_id = recipe_ingredients.product_id
                    ), 0) < recipe_ingredients.quantity
                    SQL,
                    [$household->getKey()]
                );
        });
    }
}
