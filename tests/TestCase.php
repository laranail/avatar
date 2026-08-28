<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Avatar\Tests;

use Simtabi\Laranail\Avatar\Providers\AvatarServiceProvider;
use Simtabi\Laranail\Package\Tools\Testing\IsolatedTestCase;

abstract class TestCase extends IsolatedTestCase
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [AvatarServiceProvider::class];
    }
}
