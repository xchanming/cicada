<?php declare(strict_types=1);

namespace Cicada\Core\Test\PHPUnit\Extension\DatabaseDiff;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Kernel;
use Cicada\Core\Test\PHPUnit\Extension\DatabaseDiff\Subscriber\BeforeTestMethodCalledSubscriber;
use Cicada\Core\Test\PHPUnit\Extension\DatabaseDiff\Subscriber\TestFinishedSubscriber;

/**
 * @internal
 */
#[Package('core')]
class DatabaseDiffExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $dbState = new DbState(Kernel::getConnection());

        $facade->registerSubscribers(
            new BeforeTestMethodCalledSubscriber($dbState),
            new TestFinishedSubscriber($dbState)
        );
    }
}
