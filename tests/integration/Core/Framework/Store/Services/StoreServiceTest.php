<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Services;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\StoreService;
use Cicada\Core\Framework\Store\Struct\AccessTokenStruct;
use Cicada\Core\Framework\Store\Struct\ShopUserTokenStruct;
use Cicada\Core\Framework\Test\Store\StoreClientBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class StoreServiceTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;

    private StoreService $storeService;

    protected function setUp(): void
    {
        $this->storeService = static::getContainer()->get(StoreService::class);
    }

    public function testUpdateStoreToken(): void
    {
        $adminStoreContext = $this->createAdminStoreContext();

        $newToken = 'updated-store-token';
        $accessTokenStruct = new AccessTokenStruct(
            new ShopUserTokenStruct(
                $newToken,
                new \DateTimeImmutable()
            )
        );

        $this->storeService->updateStoreToken(
            $adminStoreContext,
            $accessTokenStruct
        );

        /** @var AdminApiSource $adminSource */
        $adminSource = $adminStoreContext->getSource();
        /** @var string $userId */
        $userId = $adminSource->getUserId();
        $criteria = new Criteria([$userId]);

        $updatedUser = $this->getUserRepository()->search($criteria, $adminStoreContext)->getEntities()->first();

        static::assertEquals('updated-store-token', $updatedUser?->getStoreToken());
    }
}
