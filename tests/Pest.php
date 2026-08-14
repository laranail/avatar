<?php

declare(strict_types=1);

use Simtabi\Laranail\Avatar\Tests\TestCase;

/*
| Only Feature tests boot Laravel. The builder, the sources and the renderers
| are plain objects with no container dependency, and testing them without one
| is the cheapest proof that they stayed that way.
*/

uses(TestCase::class)->in('Feature');
