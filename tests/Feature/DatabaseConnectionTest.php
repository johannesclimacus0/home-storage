<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_db_connection(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('testing', DB::scalar('select current_database()'));
        $this->assertTrue(Schema::hasTable('users'));
    }
}
