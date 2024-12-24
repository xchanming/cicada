<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Cleanup;

use Cicada\Core\Checkout\Cart\AbstractCartPersister;
use Cicada\Core\Checkout\Cart\Cleanup\CleanupCartTaskHandler;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(CleanupCartTaskHandler::class)]
class CleanupCartTaskHandlerTest extends TestCase
{
    public function testHandle(): void
    {
        $cartPersister = $this->createMock(AbstractCartPersister::class);
        $cartPersister->expects(static::once())
            ->method('prune')
            ->with(30);

        $handler = new CleanupCartTaskHandler(
            $this->createMock(EntityRepository::class),
            $this->createMock(LoggerInterface::class),
            $cartPersister,
            30
        );

        $handler->run();
    }
}
