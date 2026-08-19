<?php

namespace Tests\Unit\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use PHPUnit\Framework\TestCase;

final class ProductPolicyTest extends TestCase
{
    public function test_authenticated_user_can_read_and_create_catalog_products(): void
    {
        $policy = new ProductPolicy;
        $user = new User;
        $product = new Product;

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $product));
        $this->assertTrue($policy->create($user));
    }
}
