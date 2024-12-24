<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\StateMachine\Api;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Exception\MissingPrivilegeException;
use Cicada\Core\Framework\Api\Response\ResponseFactoryInterface;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\Api\StateMachineActionController;
use Cicada\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(StateMachineActionController::class)]
class StateMachineActionControllerTest extends TestCase
{
    public function testTransitionWithoutPrivileges(): void
    {
        $this->expectException(MissingPrivilegeException::class);
        $this->expectExceptionMessage('{"message":"Missing privilege","missingPrivileges":["order:update"]}');

        $controller = new StateMachineActionController(
            $this->createMock(StateMachineRegistry::class),
            $this->createMock(DefinitionInstanceRegistry::class),
        );
        $controller->transitionState(
            new Request(),
            Context::createDefaultContext(new AdminApiSource(null)),
            $this->createMock(ResponseFactoryInterface::class),
            'order',
            '1234',
            'process',
        );
    }

    public function testGetAvailableTransitionsWithoutPrivileges(): void
    {
        $this->expectException(MissingPrivilegeException::class);
        $this->expectExceptionMessage('{"message":"Missing privilege","missingPrivileges":["order:read"]}');

        $controller = new StateMachineActionController(
            $this->createMock(StateMachineRegistry::class),
            $this->createMock(DefinitionInstanceRegistry::class),
        );
        $controller->getAvailableTransitions(
            new Request(),
            Context::createDefaultContext(new AdminApiSource(null)),
            'order',
            '1234',
        );
    }
}
