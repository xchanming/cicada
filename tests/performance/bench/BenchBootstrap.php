<?php declare(strict_types=1);

namespace Cicada\Tests\Bench;

require __DIR__ . '/../../../src/Core/TestBootstrapper.php';

use Cicada\Core\TestBootstrapper;

(new TestBootstrapper())
    ->setForceInstall(false)
    ->setPlatformEmbedded(false)
    ->bootstrap();
