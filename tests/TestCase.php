<?php

namespace Tests;

use Database\Seeders\RolesPermissionsSeeder;
use \Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
{
    parent::setUp();
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->seed(RolesPermissionsSeeder::class);
}

}
