<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Service\ScheduledTask;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Service\AllServiceInstaller;
use Cicada\Core\Service\ScheduledTask\InstallServicesTaskHandler;

/**
 * @internal
 */
#[CoversClass(InstallServicesTaskHandler::class)]
class InstallServicesTaskHandlerTest extends TestCase
{
    public function testRunDelegatesToInstaller(): void
    {
        $installer = $this->createMock(AllServiceInstaller::class);
        $installer->expects(static::once())->method('install');

        $handler = new InstallServicesTaskHandler(
            $this->createMock(EntityRepository::class),
            $this->createMock(LoggerInterface::class),
            $installer
        );

        $handler->run();
    }
}
