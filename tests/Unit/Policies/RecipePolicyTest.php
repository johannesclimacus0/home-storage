<?php

namespace Tests\Unit\Policies;

use App\Models\Recipe;
use App\Models\User;
use App\Policies\RecipePolicy;
use PHPUnit\Framework\TestCase;

final class RecipePolicyTest extends TestCase
{
    public function test_authenticated_user_can_list_view_and_create_recipes(): void
    {
        $policy = new RecipePolicy;
        $user = new User;
        $recipe = new Recipe;

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $recipe));
        $this->assertTrue($policy->create($user));
    }

    public function test_only_recipe_creator_can_update_and_delete_recipe(): void
    {
        $policy = new RecipePolicy;
        $creator = new User;
        $creator->id = 10;
        $outsider = new User;
        $outsider->id = 20;
        $recipe = new Recipe;
        $recipe->created_by_user_id = 10;

        $this->assertTrue($policy->update($creator, $recipe));
        $this->assertTrue($policy->delete($creator, $recipe));
        $this->assertFalse($policy->update($outsider, $recipe));
        $this->assertFalse($policy->delete($outsider, $recipe));
    }

    public function test_recipe_without_creator_cannot_be_changed(): void
    {
        $policy = new RecipePolicy;
        $user = new User;
        $user->id = 10;

        $this->assertFalse($policy->update($user, new Recipe));
        $this->assertFalse($policy->delete($user, new Recipe));
    }
}
