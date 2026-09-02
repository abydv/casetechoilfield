<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Shared base for any test that needs the app's real schema. The
 * framework's own CIUnitTestCase defaults $namespace to 'Tests\Support',
 * which has no migrations of its own — override to null so
 * DatabaseTestTrait migrates every registered namespace (App included).
 */
abstract class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;
}
