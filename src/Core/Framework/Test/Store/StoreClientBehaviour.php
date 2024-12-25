<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Store;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Services\FirstRunWizardService;
use Cicada\Core\Framework\Store\Services\InstanceService;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigEntity;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\System\User\UserEntity;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

/**
 * @internal
 */
#[Package('checkout')]
trait StoreClientBehaviour
{
    public function getStoreRequestHandler(): MockHandler
    {
        /** @var MockHandler $handler */
        $handler = static::getContainer()->get('cicada.store.mock_handler');

        return $handler;
    }

    public function getFrwRequestHandler(): MockHandler
    {
        /** @var MockHandler $handler */
        $handler = static::getContainer()->get('cicada.frw.mock_handler');

        return $handler;
    }

    #[After]
    #[Before]
    public function resetStoreMock(): void
    {
        $this->getStoreRequestHandler()->reset();
    }

    #[After]
    #[Before]
    public function resetFrwMock(): void
    {
        $this->getFrwRequestHandler()->reset();
    }

    protected function createAdminStoreContext(): Context
    {
        $userId = Uuid::randomHex();
        $storeToken = Uuid::randomHex();

        $data = [
            [
                'id' => $userId,
                'localeId' => $this->getLocaleIdOfSystemLanguage(),
                'username' => 'foobar',
                'password' => 'asdasdasdasd',
                'name' => 'Foo',
                'email' => Uuid::randomHex() . '@bar.com',
                'storeToken' => $storeToken,
            ],
        ];

        $this->getUserRepository()->create($data, Context::createDefaultContext());

        $source = new AdminApiSource($userId);
        $source->setIsAdmin(true);

        return Context::createDefaultContext($source);
    }

    protected function getStoreTokenFromContext(Context $context): string
    {
        /** @var AdminApiSource $source */
        $source = $context->getSource();

        $userId = $source->getUserId();

        if ($userId === null) {
            throw new \RuntimeException('No user id found in context');
        }

        /** @var UserCollection $users */
        $users = $this->getUserRepository()->search(new Criteria([$userId]), $context)->getEntities();

        if ($users->count() === 0) {
            throw new \RuntimeException('No user found with id ' . $userId);
        }

        $user = $users->first();
        static::assertInstanceOf(UserEntity::class, $user);

        $token = $user->getStoreToken();
        static::assertIsString($token);

        return $token;
    }

    protected function getFrwUserTokenFromContext(Context $context): ?string
    {
        /** @var AdminApiSource $source */
        $source = $context->getSource();
        $criteria = (new Criteria())->addFilter(
            new EqualsFilter('userId', $source->getUserId()),
            new EqualsFilter('key', FirstRunWizardService::USER_CONFIG_KEY_FRW_USER_TOKEN),
        );

        /** @var UserConfigEntity|null $config */
        $config = static::getContainer()->get('user_config.repository')->search($criteria, $context)->first();

        return $config ? $config->getValue()[FirstRunWizardService::USER_CONFIG_VALUE_FRW_USER_TOKEN] ?? null : null;
    }

    protected function setFrwUserToken(Context $context, string $frwUserToken): void
    {
        $source = $context->getSource();
        if (!$source instanceof AdminApiSource) {
            throw new \RuntimeException('Context with AdminApiSource expected.');
        }

        static::getContainer()->get('user_config.repository')->create([
            [
                'userId' => $source->getUserId(),
                'key' => FirstRunWizardService::USER_CONFIG_KEY_FRW_USER_TOKEN,
                'value' => [
                    FirstRunWizardService::USER_CONFIG_VALUE_FRW_USER_TOKEN => $frwUserToken,
                ],
            ],
        ], Context::createDefaultContext());
    }

    protected function setLicenseDomain(?string $licenseDomain): void
    {
        $systemConfigService = static::getContainer()->get(SystemConfigService::class);

        $systemConfigService->set(
            'core.store.licenseHost',
            $licenseDomain
        );
    }

    protected function setShopSecret(string $shopSecret): void
    {
        $systemConfigService = static::getContainer()->get(SystemConfigService::class);

        $systemConfigService->set(
            'core.store.shopSecret',
            $shopSecret
        );
    }

    protected function getCicadaVersion(): string
    {
        $instanceService = static::getContainer()->get(InstanceService::class);

        return $instanceService->getCicadaVersion();
    }

    /**
     * @return EntityRepository<UserCollection>
     */
    protected function getUserRepository(): EntityRepository
    {
        return static::getContainer()->get('user.repository');
    }
}
