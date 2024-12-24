<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Controller;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Administration\Controller\UserConfigController;
use Cicada\Core\Framework\Api\ApiException;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\Context\Exception\InvalidContextSourceException;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Api\Controller\Exception\ExpectedUserHttpException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigCollection;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigDefinition;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigEntity;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(UserConfigController::class)]
class UserConfigControllerTest extends TestCase
{
    /**
     * @var StaticEntityRepository<UserConfigCollection>
     */
    private StaticEntityRepository $userConfigRepository;

    private UserConfigController $userConfigController;

    private Context $context;

    protected function setup(): void
    {
        $this->userConfigRepository = new StaticEntityRepository([], new UserConfigDefinition());
        $this->userConfigController = new UserConfigController(
            $this->userConfigRepository,
            $this->createMock(Connection::class),
        );
        $this->context = Context::createDefaultContext(new AdminApiSource(Uuid::randomHex()));
    }

    public function testGetConfigMeReturnsEmptyData(): void
    {
        $this->userConfigRepository->addSearch(new UserConfigCollection());

        $response = $this->userConfigController->getConfigMe($this->context, new Request());

        static::assertNotFalse($response->getContent());
        static::assertJsonStringEqualsJsonString('{"data":[]}', $response->getContent());
    }

    /**
     * @deprecated tag:v6.7.0 - Remove in 6.7
     */
    #[DisabledFeatures(['v6.7.0.0'])]
    public function testGetConfigMeThrowsExpectedUserHttpExceptionWhenNoUserId(): void
    {
        $this->expectExceptionObject(new ExpectedUserHttpException());

        $response = $this->userConfigController->getConfigMe(Context::createDefaultContext(new AdminApiSource(null)), new Request());

        static::assertNotFalse($response->getContent());
        static::assertJsonStringEqualsJsonString('{"data":[]}', $response->getContent());
    }

    public function testGetConfigMeThrowsApiExceptionWhenNoUserId(): void
    {
        $this->expectExceptionObject(ApiException::userNotLoggedIn());

        $response = $this->userConfigController->getConfigMe(Context::createDefaultContext(new AdminApiSource(null)), new Request());
    }

    public function testGetConfigMeThrowsInvalidContextSourceExceptionWhenWrongSource(): void
    {
        $this->expectExceptionObject(new InvalidContextSourceException(AdminApiSource::class, SystemSource::class));

        $response = $this->userConfigController->getConfigMe(Context::createDefaultContext(), new Request());

        static::assertNotFalse($response->getContent());
        static::assertJsonStringEqualsJsonString('{"data":[]}', $response->getContent());
    }

    public function testGetConfigMeReturnsDataWithKeys(): void
    {
        $userConfigEntity = new UserConfigEntity();
        $userConfigEntity->setUniqueIdentifier(Uuid::randomHex());
        $userConfigEntity->setKey('testKey');
        $this->userConfigRepository->addSearch(new UserConfigCollection([$userConfigEntity]));

        $response = $this->userConfigController->getConfigMe($this->context, new Request(['keys' => ['testKey']]));

        static::assertNotFalse($response->getContent());
        static::assertJsonStringEqualsJsonString('{"data":{"testKey": null}}', $response->getContent());
    }

    public function testUpdateConfigMeReturnsEmptyDataWhenNoPostUpdateConfigs(): void
    {
        $response = $this->userConfigController->updateConfigMe($this->context, new Request([], []));

        static::assertNotFalse($response->getContent());
        static::assertJsonStringEqualsJsonString('{}', $response->getContent());
        static::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testUpdateConfigPerformsMassUpsertEmptyWhenPostUpdateConfigs(): void
    {
        $userConfigEntity = new UserConfigEntity();
        $userConfigEntity->setId(Uuid::randomHex());
        $userConfigEntity->setUniqueIdentifier(Uuid::randomHex());
        $userConfigEntity->setKey('testKey');

        $this->userConfigRepository->addSearch(new UserConfigCollection([$userConfigEntity]));

        $response = $this->userConfigController->updateConfigMe($this->context, new Request([], ['product' => true, 'testKey' => true]));

        static::assertNotFalse($response->getContent());
        static::assertJsonStringEqualsJsonString('{}', $response->getContent());
        static::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
