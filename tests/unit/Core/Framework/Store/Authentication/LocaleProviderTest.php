<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Authentication;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\EntityNotFoundException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\LocaleProvider;
use Cicada\Core\System\Locale\LocaleEntity;
use Cicada\Core\System\User\UserCollection;
use Cicada\Core\System\User\UserDefinition;
use Cicada\Core\System\User\UserEntity;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LocaleProvider::class)]
class LocaleProviderTest extends TestCase
{
    public function testGetLocaleFromContextReturnsEnGbInSystemSource(): void
    {
        $provider = new LocaleProvider(static::createMock(EntityRepository::class));

        static::assertEquals('en-GB', $provider->getLocaleFromContext(Context::createDefaultContext()));
    }

    public function testGetLocaleFromContextReturnsEnGbIfNoUserIsAssociated(): void
    {
        $provider = new LocaleProvider(static::createMock(EntityRepository::class));

        static::assertEquals(
            'en-GB',
            $provider->getLocaleFromContext(Context::createDefaultContext(
                new AdminApiSource(null, 'i-am-an-integration')
            ))
        );
    }

    public function testGetLocaleFromContextReturnsLocaleFromUser(): void
    {
        $context = Context::createDefaultContext(new AdminApiSource('user-id', null));

        $userLocale = new LocaleEntity();
        $userLocale->setCode('user-locale');

        $user = new UserEntity();
        $user->setUniqueIdentifier('user-identifier');
        $user->setLocale($userLocale);

        $userRepository = static::createMock(EntityRepository::class);
        $userRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                UserDefinition::ENTITY_NAME,
                1,
                new UserCollection([$user]),
                null,
                new Criteria(),
                $context
            ));

        $provider = new LocaleProvider($userRepository);

        static::assertEquals('user-locale', $provider->getLocaleFromContext($context));
    }

    public function testGetLocaleFromContextThrowsIfAssociatedUserCanNotBeFound(): void
    {
        $context = Context::createDefaultContext(new AdminApiSource('user-id', null));

        $userRepository = static::createMock(EntityRepository::class);
        $userRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                UserDefinition::ENTITY_NAME,
                1,
                new UserCollection([]),
                null,
                new Criteria(),
                $context
            ));

        $provider = new LocaleProvider($userRepository);

        static::expectException(EntityNotFoundException::class);
        $provider->getLocaleFromContext($context);
    }
}
