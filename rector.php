<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

/**
 * Pinned to the **php84** set, matching this package's `^8.4.1 || ^8.5` floor.
 *
 * Not php85: the 8.5 set would rewrite code into syntax that parses on the newer CI job and fails
 * on the older one, which is the quietest possible way to break a supported version. It is also why
 * `Data\Appearance` re-invokes its constructor instead of using `clone ($this, [...])`.
 */
return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/tests/Fixtures',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withImportNames(removeUnusedImports: true);
