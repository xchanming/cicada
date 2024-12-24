<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Service\MessageHandler;

use Cicada\Core\Service\Message\UpdateServiceMessage;
use Cicada\Core\Service\MessageHandler\UpdateServiceHandler;
use Cicada\Core\Service\ServiceLifecycle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UpdateServiceHandler::class)]
class UpdateServiceHandlerTest extends TestCase
{
    public function testHandlerDelegatesToServiceLifecycle(): void
    {
        $lifecycle = $this->createMock(ServiceLifecycle::class);
        $lifecycle->expects(static::once())->method('update')->with('MyCoolService');

        $handler = new UpdateServiceHandler($lifecycle);
        $handler->__invoke(new UpdateServiceMessage('MyCoolService'));
    }
}
