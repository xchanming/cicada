<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\User\Api;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Cicada\Core\Framework\Log\Monolog\DoctrineSQLHandler;
use Cicada\Core\Framework\Log\Monolog\ExcludeFlowEventHandler;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\EventDispatcherBehaviour;
use Cicada\Core\Maintenance\User\Service\UserProvisioner;
use Cicada\Core\System\User\Aggregate\UserRecovery\UserRecoveryEntity;
use Cicada\Core\System\User\Recovery\UserRecoveryRequestEvent;
use Cicada\Core\System\User\Recovery\UserRecoveryService;

/**
 * @internal
 */
#[Package('services-settings')]
class UserRecoveryControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;
    use EventDispatcherBehaviour;

    private const VALID_EMAIL = UserProvisioner::USER_EMAIL_FALLBACK;

    public function testUpdateUserPassword(): void
    {
        $this->createRecovery(self::VALID_EMAIL);

        $this->getBrowser()->request(
            'PATCH',
            '/api/_action/user/user-recovery/password',
            [
                'hash' => $this->getHash(),
                'password' => 'NewPassword!',
                'passwordConfirm' => 'NewPassword!',
            ]
        );

        static::assertEquals(200, $this->getBrowser()->getResponse()->getStatusCode());
    }

    public function testUpdateUserPasswordWithInvalidHash(): void
    {
        $this->createRecovery(self::VALID_EMAIL);

        $this->getBrowser()->request(
            'PATCH',
            '/api/_action/user/user-recovery/password',
            [
                'hash' => 'invalid',
                'password' => 'NewPassword!',
                'passwordConfirm' => 'NewPassword!',
            ]
        );

        static::assertEquals(400, $this->getBrowser()->getResponse()->getStatusCode());
    }

    public function testCreateUserRecovery(): void
    {
        $logger = static::getContainer()->get('monolog.logger.business_events');
        $handlers = $logger->getHandlers();
        $logger->setHandlers([
            new ExcludeFlowEventHandler(static::getContainer()->get(DoctrineSQLHandler::class), [
                UserRecoveryRequestEvent::EVENT_NAME,
            ]),
        ]);

        $dispatchedEvent = null;

        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            UserRecoveryRequestEvent::EVENT_NAME,
            function (UserRecoveryRequestEvent $event) use (&$dispatchedEvent): void {
                $dispatchedEvent = $event;
            },
        );
        $this->getBrowser()->request(
            'POST',
            '/api/_action/user/user-recovery',
            [
                'email' => self::VALID_EMAIL,
            ]
        );

        static::assertEquals(200, $this->getBrowser()->getResponse()->getStatusCode());

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('user.email', self::VALID_EMAIL));

        $userRecovery = static::getContainer()->get('user_recovery.repository')->search(
            $criteria,
            Context::createDefaultContext()
        )->first();

        static::assertNotNull($userRecovery);
        static::assertNotNull($dispatchedEvent);

        // excluded events and its mail events should not be logged
        $originalEvent = $dispatchedEvent->getName();
        $logCriteria = new Criteria();
        $logCriteria->addFilter(new OrFilter([
            new EqualsFilter('message', $originalEvent),
            new EqualsFilter('context.additionalData.eventName', $originalEvent),
        ]));

        $logEntries = static::getContainer()->get('log_entry.repository')->search(
            $logCriteria,
            Context::createDefaultContext()
        );

        static::assertCount(0, $logEntries);

        $this->resetEventDispatcher();
        $logger->setHandlers($handlers);
    }

    private function createRecovery(string $email): void
    {
        static::getContainer()->get(UserRecoveryService::class)->generateUserRecovery(
            $email,
            Context::createDefaultContext()
        );
    }

    private function getHash(): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        static::assertInstanceOf(UserRecoveryEntity::class, $recovery = static::getContainer()->get('user_recovery.repository')->search(
            $criteria,
            Context::createDefaultContext()
        )->first());

        return $recovery->getHash();
    }
}
