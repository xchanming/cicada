<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\EntityProtection;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Plugin\PluginDefinition;
use Cicada\Core\Framework\Test\DataAbstractionLayer\EntityProtection\_fixtures\PluginProtectionExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\EntityProtection\_fixtures\SystemConfigExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\EntityProtection\_fixtures\UserAccessKeyExtension;
use Cicada\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SystemConfig\SystemConfigDefinition;
use Cicada\Core\System\User\Aggregate\UserAccessKey\UserAccessKeyDefinition;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class EntityProtectionValidatorTest extends TestCase
{
    use AdminApiTestBehaviour;
    use DataAbstractionLayerFieldTestBehaviour;
    use IntegrationTestBehaviour;

    protected function setUp(): void
    {
        $this->registerDefinitionWithExtensions(PluginDefinition::class, PluginProtectionExtension::class);
        $this->registerDefinitionWithExtensions(SystemConfigDefinition::class, SystemConfigExtension::class);
        $this->registerDefinitionWithExtensions(UserAccessKeyDefinition::class, UserAccessKeyExtension::class);
    }

    #[DataProvider('blockedApiRequest')]
    #[Group('slow')]
    public function testItBlocksApiAccess(string $method, string $url): void
    {
        $this->getBrowser()
            ->request(
                $method,
                '/api/' . $url
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @return array<array<string>>
     */
    public static function blockedApiRequest(): array
    {
        return [
            ['GET', 'plugin/' . Uuid::randomHex()], // detail
            ['GET', 'plugin'], // list
            ['POST', 'plugin'], // create
            ['PATCH', 'plugin/' . Uuid::randomHex()], // update
            ['DELETE', 'plugin/' . Uuid::randomHex()], // delete
            ['POST', 'search/plugin'], // search
            ['POST', 'search-ids/plugin'], // search ids

            // nested routes
            ['POST', 'search/user/' . Uuid::randomHex() . '/access-keys'], // search
            ['POST', 'search-ids/user/' . Uuid::randomHex() . '/access-keys'], // search ids
        ];
    }

    public function testItAllowsReadsOnEntitiesWithWriteProtectionOnly(): void
    {
        $this->getBrowser()
            ->request(
                'GET',
                '/api/system-config'
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertNotEquals(403, $response->getStatusCode(), $response->getContent());

        $this->getBrowser()
            ->request(
                'GET',
                '/api/system-config/' . Uuid::randomHex()
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertNotEquals(403, $response->getStatusCode(), $response->getContent());

        $this->getBrowser()
            ->request(
                'POST',
                '/api/system-config'
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testItBlocksReadsOnForbiddenAssociations(): void
    {
        $this->getBrowser()
            ->request(
                'POST',
                '/api/search/user',
                [
                    'associations' => [
                        'accessKeys' => [],
                    ],
                ]
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertEquals(403, $response->getStatusCode(), $response->getContent());

        $this->getBrowser()
            ->request(
                'POST',
                '/api/search/user',
                [
                    'associations' => [
                        'avatarMedia' => [],
                    ],
                ]
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertNotEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testItBlocksReadsOnForbiddenNestedAssociations(): void
    {
        $this->getBrowser()
            ->request(
                'POST',
                '/api/search/media',
                [
                    'associations' => [
                        'user' => [
                            'associations' => [
                                'accessKeys' => [],
                            ],
                        ],
                    ],
                ]
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertEquals(403, $response->getStatusCode(), $response->getContent());

        $this->getBrowser()
            ->request(
                'POST',
                '/api/search/media',
                [
                    'associations' => [
                        'user' => [
                            'associations' => [
                                'avatarMedia' => [],
                            ],
                        ],
                    ],
                ]
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertNotEquals(403, $response->getStatusCode(), $response->getContent());
    }

    public function testItDoesNotValidateCascadeDeletes(): void
    {
        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = static::getContainer()->get('sales_channel.repository');
        $countBefore = $salesChannelRepository->search(new Criteria(), Context::createDefaultContext())->getTotal();

        // system_config has a cascade delete on sales_channel
        $this->getBrowser()
            ->request(
                'DELETE',
                '/api/sales-channel/' . TestDefaults::SALES_CHANNEL
            );

        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertEquals(204, $response->getStatusCode(), $response->getContent());

        static::assertEquals(
            $countBefore - 1,
            $salesChannelRepository->search(new Criteria(), Context::createDefaultContext())->getTotal()
        );
    }
}
