<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Authentication;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\Exception\InvalidContextSourceUserException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\AbstractStoreRequestOptionsProvider;
use Cicada\Core\Framework\Store\Authentication\StoreRequestOptionsProvider;
use Cicada\Core\Framework\Test\Store\StoreClientBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class StoreRequestOptionsProviderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;

    private AbstractStoreRequestOptionsProvider $storeRequestOptionsProvider;

    private Context $storeContext;

    protected function setUp(): void
    {
        $this->storeRequestOptionsProvider = static::getContainer()->get(StoreRequestOptionsProvider::class);
        $this->storeContext = $this->createAdminStoreContext();
    }

    public function testGetAuthenticationHeadersHasUserStoreTokenAndShopSecret(): void
    {
        $shopSecret = 'im-a-super-safe-secret';

        $this->setShopSecret($shopSecret);
        $headers = $this->storeRequestOptionsProvider->getAuthenticationHeader($this->storeContext);

        static::assertEquals([
            'X-Cicada-Platform-Token' => $this->getStoreTokenFromContext($this->storeContext),
            'X-Cicada-Shop-Secret' => $shopSecret,
        ], $headers);
    }

    public function testGetAuthenticationHeadersUsesFirstStoreTokenFoundIfContextIsSystemSource(): void
    {
        $shopSecret = 'im-a-super-safe-secret';

        $this->setShopSecret($shopSecret);
        $headers = $this->storeRequestOptionsProvider->getAuthenticationHeader(Context::createDefaultContext());

        static::assertEquals([
            'X-Cicada-Platform-Token' => $this->getStoreTokenFromContext($this->storeContext),
            'X-Cicada-Shop-Secret' => $shopSecret,
        ], $headers);
    }

    public function testGetAuthenticationHeadersThrowsForIntegrations(): void
    {
        $context = new Context(new AdminApiSource(null, Uuid::randomHex()));

        static::expectException(InvalidContextSourceUserException::class);
        $this->storeRequestOptionsProvider->getAuthenticationHeader($context);
    }

    public function testGetDefaultQueriesReturnsLanguageFromContext(): void
    {
        $queries = $this->storeRequestOptionsProvider->getDefaultQueryParameters($this->storeContext);

        static::assertArrayHasKey('language', $queries);
        static::assertEquals(
            $this->getLanguageFromContext($this->storeContext),
            $queries['language']
        );
    }

    public function testGetDefaultQueriesReturnsCicadaVersion(): void
    {
        $queries = $this->storeRequestOptionsProvider->getDefaultQueryParameters($this->storeContext);

        static::assertArrayHasKey('cicadaVersion', $queries);
        static::assertEquals($this->getCicadaVersion(), $queries['cicadaVersion']);
    }

    public function testGetDefaultQueriesDoesHaveDomainSetEvenIfLicenseDomainIsNull(): void
    {
        $this->setLicenseDomain(null);

        $queries = $this->storeRequestOptionsProvider->getDefaultQueryParameters($this->storeContext);

        static::assertArrayHasKey('domain', $queries);
        static::assertEquals('', $queries['domain']);
    }

    public function testGetDefaultQueriesDoesHaveDomainSetIfLicenseDomainIsSet(): void
    {
        $this->setLicenseDomain('cicada.swag');

        $queries = $this->storeRequestOptionsProvider->getDefaultQueryParameters($this->storeContext);

        static::assertArrayHasKey('domain', $queries);
        static::assertEquals('cicada.swag', $queries['domain']);
    }

    public function testGetDefaultQueriesWithLicenseDomain(): void
    {
        $this->setLicenseDomain('new-license-domain');

        $queries = $this->storeRequestOptionsProvider->getDefaultQueryParameters($this->storeContext);

        static::assertArrayHasKey('domain', $queries);
        static::assertEquals('new-license-domain', $queries['domain']);
    }

    private function getLanguageFromContext(Context $context): string
    {
        /** @var AdminApiSource $contextSource */
        $contextSource = $context->getSource();
        $userId = $contextSource->getUserId();

        static::assertIsString($userId);

        $criteria = (new Criteria([$userId]))->addAssociation('locale');

        $user = $this->getUserRepository()->search($criteria, $context)->getEntities()->first();
        static::assertNotNull($user);
        static::assertNotNull($user->getLocale());

        return $user->getLocale()->getCode();
    }
}
