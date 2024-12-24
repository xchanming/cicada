<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Administration\System\SalesChannel\Subscriber;

use Cicada\Administration\System\SalesChannel\Subscriber\SalesChannelUserConfigSubscriber;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\TestUser;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SalesChannelUserConfigSubscriberTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    public function testDeleteWillRemoveUserConfigs(): void
    {
        $admin = TestUser::createNewTestUser(static::getContainer()->get(Connection::class), ['product:read']);
        $context = Context::createDefaultContext();

        $salesChannelId1 = Uuid::randomHex();
        $salesChannelId2 = Uuid::randomHex();

        /** @var EntityRepository<UserConfigCollection> $userConfigRepository */
        $userConfigRepository = static::getContainer()->get('user_config.repository');
        $userConfigId = Uuid::randomHex();
        $userConfigRepository->create([
            [
                'id' => $userConfigId,
                'userId' => $admin->getUserId(),
                'key' => SalesChannelUserConfigSubscriber::CONFIG_KEY,
                'value' => [$salesChannelId1, $salesChannelId2],
                'createdAt' => new \DateTime(),
            ],
        ], $context);

        $search = $userConfigRepository->search(new Criteria([$userConfigId]), $context)
            ->getEntities()
            ->first();

        static::assertNotNull($search);
        static::assertIsArray($search->getValue());
        static::assertCount(2, $search->getValue());

        $this->createSalesChannel(['id' => $salesChannelId1]);
        $this->createSalesChannel(['id' => $salesChannelId2]);

        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');
        $salesChannelRepository->delete([['id' => $salesChannelId1], ['id' => $salesChannelId2]], $context);

        $search = $userConfigRepository->search(new Criteria([$userConfigId]), $context)
            ->getEntities()
            ->first();

        static::assertNotNull($search);
        static::assertIsArray($search->getValue());
        static::assertCount(0, $search->getValue());
    }
}
