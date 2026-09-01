<?php

namespace App\Contracts\Recipes;

use App\Enums\RecipeAvailabilityFilter;
use App\Models\Household;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RecipeAvailabilityRepository
{
    public function paginateForHousehold(
        Household $household,
        RecipeAvailabilityFilter $filter,
        int $perPage
    ): LengthAwarePaginator;

    /**
     * @param  Collection<int, int>  $productIds
     * @return array<int, string>
     */
    public function quantitiesForProducts(
        Household $household,
        Collection $productIds
    ): array;
}
