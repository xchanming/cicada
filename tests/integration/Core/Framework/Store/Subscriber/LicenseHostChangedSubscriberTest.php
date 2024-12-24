<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Subscriber;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\System\User\UserEntity;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class LicenseHostChangedSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testDeletesShopSecretAndLogsOutAllUsers(): void
    {
        $context = Context::createDefaultContext();

        $systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $systemConfigService->set('core.store.licenseHost', 'host');
        $systemConfigService->set('core.store.shopSecret', 'shop-s3cr3t');

        /** @var EntityRepository $userRepository */
        $userRepository = static::getContainer()->get('user.repository');

        /** @var UserEntity $adminUser */
        $adminUser = $userRepository->search(new Criteria(), $context)->first();

        $userRepository->create([
            [
                'localeId' => $adminUser->getLocaleId(),
                'username' => 'admin2',
                'password' => TestDefaults::HASHED_PASSWORD,
                'firstName' => 'admin2',
                'lastName' => 'admin2',
                'email' => 'admin2@cicada.com',
                'storeToken' => null,
            ],
            [
                'localeId' => $adminUser->getLocaleId(),
                'username' => 'admin3',
                'password' => TestDefaults::HASHED_PASSWORD,
                'firstName' => 'admin3',
                'lastName' => 'admin3',
                'email' => 'admin3@cicada.com',
                'storeToken' => null,
            ],
        ], $context);

        $systemConfigService->set('core.store.licenseHost', 'otherhost');
        $adminUsers = $this->fetchAllAdminUsers();

        static::assertCount(3, $adminUsers);
        foreach ($adminUsers as $adminUser) {
            static::assertNull($adminUser['store_token']);
        }

        static::assertNull($systemConfigService->get('core.store.shopSecret'));
    }

    /**
     * @return array<array<string, string>>
     */
    private function fetchAllAdminUsers(): array
    {
        return static::getContainer()->get(Connection::class)->executeQuery(
            'SELECT * FROM user'
        )->fetchAllAssociative();
    }
}
