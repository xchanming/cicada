<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Authentication;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\LocaleProvider;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class LocaleProviderTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $userRepository;

    private LocaleProvider $localeProvider;

    protected function setUp(): void
    {
        $this->userRepository = static::getContainer()->get('user.repository');
        $this->localeProvider = static::getContainer()->get(LocaleProvider::class);
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
            'password' => TestDefaults::HASHED_PASSWORD,
            'phone' => (string) rand(10000000000, 99999999999),
            'locale' => [
                'code' => $userLocale,
                'name' => 'testLocale',
                'territory' => 'somewhere',
            ],
        ]], Context::createDefaultContext());

        $context = Context::createDefaultContext(new AdminApiSource($userId));

        $locale = $this->localeProvider->getLocaleFromContext($context);

        static::assertEquals($userLocale, $locale);
    }

    public function testGetLocaleFromContextReturnsEnglishForSystemContext(): void
    {
        $locale = $this->localeProvider->getLocaleFromContext(Context::createDefaultContext());

        static::assertEquals('zh-CN', $locale);
    }

    public function testGetLocaleFromContextReturnsEnglishForIntegrations(): void
    {
        $locale = $this->localeProvider->getLocaleFromContext(
            Context::createDefaultContext(new AdminApiSource(null, Uuid::randomHex()))
        );

        static::assertEquals('zh-CN', $locale);
    }
}
