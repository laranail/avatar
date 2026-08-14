<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Simtabi\Laranail\Avatar\Providers\AvatarServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [AvatarServiceProvider::class];
    }
}
