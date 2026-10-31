<?php
namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Testcontainers\Container\StartedGenericContainer;
use Testcontainers\Modules\PostgresContainer;
use Testcontainers\Wait\WaitForExec;

abstract class TestCase extends BaseTestCase
{
    private static ?StartedGenericContainer $postgresContainer = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$postgresContainer = (new PostgresContainer(
            '14-alpine',
            'auth_test',
            'auth_test_password',
            'auth_test',
        ))->withWait(new WaitForExec(
            ['pg_isready', '-h', '127.0.0.1', '-U', 'auth_test'],
            timeout: 60_000,
        ))->start();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => self::$postgresContainer->getHost(),
            'database.connections.pgsql.port' => self::$postgresContainer->getFirstMappedPort(),
            'database.connections.pgsql.database' => 'auth_test',
            'database.connections.pgsql.username' => 'auth_test',
            'database.connections.pgsql.password' => 'auth_test_password',
        ]);
        DB::purge('pgsql');
    }

    public static function tearDownAfterClass(): void
    {
        self::$postgresContainer?->stop();
        self::$postgresContainer = null;

        parent::tearDownAfterClass();
    }
}
