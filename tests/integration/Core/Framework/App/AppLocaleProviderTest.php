<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\App\AppLocaleProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AppLocaleProviderTest extends TestCase
{
    use IntegrationTestBehaviour;

    private AppLocaleProvider $localeProvider;

    private EntityRepository $userRepository;

    protected function setUp(): void
    {
        $this->localeProvider = static::getContainer()->get(AppLocaleProvider::class);
        $this->userRepository = static::getContainer()->get('user.repository');
    }

    public function testGetLocaleWithSystemSource(): void
    {
        $locale = $this->localeProvider->getLocaleFromContext(Context::createDefaultContext());

        static::assertSame('en-GB', $locale);
    }

    public function testGetLocaleWithSalesChannelSource(): void
    {
        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $locale = $this->localeProvider->getLocaleFromContext($context->getContext());

        static::assertSame('en-GB', $locale);
    }

    public function testGetLocaleFromContextReturnsLocaleFromUser(): void
    {
        $userId = Uuid::randomHex();
        $userLocale = 'abc-de';

        $this->userRepository->create([[
            'id' => $userId,
            'username' => 'testUser',
            'name' => 'first',
            'email' => 'first@last.de',
            'password' => '12345678',
            'phone' => (string) rand(10000000000, 99999999999),
            'locale' => [
                'code' => $userLocale,
                'name' => 'testLocale',
                'territory' => 'somewhere',
            ],
        ]], Context::createDefaultContext());

        $context = Context::createDefaultContext(new AdminApiSource($userId));

        $locale = $this->localeProvider->getLocaleFromContext($context);

        static::assertSame($userLocale, $locale);
    }

    public function testGetLocaleFromContextReturnsEnglishForSystemContext(): void
    {
        $locale = $this->localeProvider->getLocaleFromContext(Context::createDefaultContext());

        static::assertSame('en-GB', $locale);
    }

    public function testGetLocaleFromContextReturnsEnglishForIntegrations(): void
    {
        $locale = $this->localeProvider->getLocaleFromContext(
            Context::createDefaultContext(new AdminApiSource(null, Uuid::randomHex()))
        );

        static::assertSame('en-GB', $locale);
    }
}
