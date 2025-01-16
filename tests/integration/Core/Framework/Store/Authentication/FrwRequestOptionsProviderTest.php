<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Authentication;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\Exception\InvalidContextSourceException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\FrwRequestOptionsProvider;
use Cicada\Core\Framework\Store\Services\FirstRunWizardService;
use Cicada\Core\Framework\Test\Store\StoreClientBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class FrwRequestOptionsProviderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StoreClientBehaviour;

    private Context $context;

    private FrwRequestOptionsProvider $optionsProvider;

    private EntityRepository $userConfigRepository;

    protected function setUp(): void
    {
        $this->context = $this->createAdminStoreContext();
        $this->optionsProvider = static::getContainer()->get(FrwRequestOptionsProvider::class);
        $this->userConfigRepository = static::getContainer()->get('user_config.repository');
    }

    public function testSetsFrwUserTokenIfPresentInUserConfig(): void
    {
        $frwUserToken = 'a84a653a57dc43a48ded4275524893cf';

        $source = $this->context->getSource();
        static::assertInstanceOf(AdminApiSource::class, $source);

        $this->userConfigRepository->create([
            [
                'userId' => $source->getUserId(),
                'key' => FirstRunWizardService::USER_CONFIG_KEY_FRW_USER_TOKEN,
                'value' => [
                    FirstRunWizardService::USER_CONFIG_VALUE_FRW_USER_TOKEN => $frwUserToken,
                ],
            ],
        ], Context::createDefaultContext());

        $headers = $this->optionsProvider->getAuthenticationHeader($this->context);

        static::assertArrayHasKey('X-Cicada-Token', $headers);
        static::assertEquals($frwUserToken, $headers['X-Cicada-Token']);
    }

    public function testRemovesEmptyAuthenticationHeaderIfFrwUserTokenIsNotSet(): void
    {
        $headers = $this->optionsProvider->getAuthenticationHeader($this->context);

        static::assertEmpty($headers);
    }

    public function testThrowsInvalidContextSourceExceptionIfNotAdminApiSource(): void
    {
        static::expectException(InvalidContextSourceException::class);

        $this->optionsProvider->getAuthenticationHeader(Context::createDefaultContext());
    }
}
