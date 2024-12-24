<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Store\Authentication;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\Exception\InvalidContextSourceException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Authentication\AbstractStoreRequestOptionsProvider;
use Cicada\Core\Framework\Store\Authentication\FrwRequestOptionsProvider;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigCollection;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigDefinition;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigEntity;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(FrwRequestOptionsProvider::class)]
class FrwRequestOptionsProviderTest extends TestCase
{
    public function testGetAuthenticationHeaderReturnsFrwToken(): void
    {
        $context = Context::createDefaultContext(new AdminApiSource('user-id'));

        $userConfig = new UserConfigEntity();
        $userConfig->setUniqueIdentifier('user-config-id');
        $userConfig->setValue([
            'frwUserToken' => 'frw-user-token',
        ]);

        $result = new EntitySearchResult(
            UserConfigDefinition::ENTITY_NAME,
            1,
            new UserConfigCollection([$userConfig]),
            null,
            new Criteria(),
            $context
        );

        $userConfigRepositoryMock = static::createMock(EntityRepository::class);
        $userConfigRepositoryMock->expects(static::once())
            ->method('search')
            ->willReturn($result);

        $innerOptionsProvider = static::createMock(AbstractStoreRequestOptionsProvider::class);

        $frwRequestOptionsProvider = new FrwRequestOptionsProvider(
            $innerOptionsProvider,
            $userConfigRepositoryMock
        );

        static::assertEquals([
            'X-Cicada-Token' => 'frw-user-token',
        ], $frwRequestOptionsProvider->getAuthenticationHeader($context));
    }

    public function testGetAuthenticationHeaderReturnsEmptyArrayIfFrwTokenIsNull(): void
    {
        $context = Context::createDefaultContext(new AdminApiSource('user-id'));

        $userConfig = new UserConfigEntity();
        $userConfig->setUniqueIdentifier('user-config-id');
        $userConfig->setValue([]);

        $result = new EntitySearchResult(
            UserConfigDefinition::ENTITY_NAME,
            1,
            new UserConfigCollection([$userConfig]),
            null,
            new Criteria(),
            $context
        );

        $userConfigRepositoryMock = static::createMock(EntityRepository::class);
        $userConfigRepositoryMock->expects(static::once())
            ->method('search')
            ->willReturn($result);

        $innerOptionsProvider = static::createMock(AbstractStoreRequestOptionsProvider::class);

        $frwRequestOptionsProvider = new FrwRequestOptionsProvider(
            $innerOptionsProvider,
            $userConfigRepositoryMock
        );

        static::assertEquals([], $frwRequestOptionsProvider->getAuthenticationHeader($context));
    }

    public function testGetAuthenticationHeaderReturnsEmptyArrayIfUserConfigCanNotBeFound(): void
    {
        $context = Context::createDefaultContext(new AdminApiSource('user-id'));

        $result = new EntitySearchResult(
            UserConfigDefinition::ENTITY_NAME,
            1,
            new UserConfigCollection(),
            null,
            new Criteria(),
            $context
        );

        $userConfigRepositoryMock = static::createMock(EntityRepository::class);
        $userConfigRepositoryMock->expects(static::once())
            ->method('search')
            ->willReturn($result);

        $innerOptionsProvider = static::createMock(AbstractStoreRequestOptionsProvider::class);

        $frwRequestOptionsProvider = new FrwRequestOptionsProvider(
            $innerOptionsProvider,
            $userConfigRepositoryMock
        );

        static::assertEquals([], $frwRequestOptionsProvider->getAuthenticationHeader($context));
    }

    public function testGetAuthenticationHeaderThrowsIfContextIsNoAdminApiSource(): void
    {
        $context = Context::createDefaultContext();

        $userConfigRepositoryMock = static::createMock(EntityRepository::class);
        $userConfigRepositoryMock->expects(static::never())
            ->method('search');

        $innerOptionsProvider = static::createMock(AbstractStoreRequestOptionsProvider::class);

        $frwRequestOptionsProvider = new FrwRequestOptionsProvider(
            $innerOptionsProvider,
            $userConfigRepositoryMock
        );

        static::expectException(InvalidContextSourceException::class);
        $frwRequestOptionsProvider->getAuthenticationHeader($context);
    }

    public function testGetDefaultQueryParametersDelegatesToInnerProvider(): void
    {
        $context = Context::createDefaultContext();

        $userConfigRepositoryMock = static::createMock(EntityRepository::class);

        $innerOptionsProvider = static::createMock(AbstractStoreRequestOptionsProvider::class);
        $innerOptionsProvider->expects(static::once())
            ->method('getDefaultQueryParameters')
            ->with($context)
            ->willReturn([
                'queries' => 'some-queries',
            ]);

        $frwRequestOptionsProvider = new FrwRequestOptionsProvider(
            $innerOptionsProvider,
            $userConfigRepositoryMock
        );

        $queries = $frwRequestOptionsProvider->getDefaultQueryParameters($context);

        static::assertEquals([
            'queries' => 'some-queries',
        ], $queries);
    }
}
