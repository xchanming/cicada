<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Controller;

use Cicada\Administration\Controller\AdminExtensionApiController;
use Cicada\Administration\Controller\Exception\AppByNameNotFoundException;
use Cicada\Administration\Controller\Exception\MissingAppSecretException;
use Cicada\Core\Framework\App\ActionButton\Executor;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\AppException;
use Cicada\Core\Framework\App\Exception\AppNotFoundException;
use Cicada\Core\Framework\App\Hmac\QuerySigner;
use Cicada\Core\Framework\App\Manifest\Exception\UnallowedHostException;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(AdminExtensionApiController::class)]
class AdminExtensionApiControllerTest extends TestCase
{
    private MockObject&AppPayloadServiceHelper $appPayloadServiceHelper;

    private Context $context;

    private MockObject&EntityRepository $entityRepository;

    private MockObject&Executor $executor;

    private MockObject&QuerySigner $querySigner;

    private AdminExtensionApiController $controller;

    protected function setUp(): void
    {
        $this->appPayloadServiceHelper = $this->createMock(AppPayloadServiceHelper::class);
        $this->context = Context::createDefaultContext();
        $this->querySigner = $this->createMock(QuerySigner::class);
        $this->executor = $this->createMock(Executor::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        $this->controller = new AdminExtensionApiController(
            $this->executor,
            $this->appPayloadServiceHelper,
            $this->entityRepository,
            $this->querySigner
        );
    }

    public function testRunActionThrowsAppByNameNotFoundExceptionWhenAppIsNotFound(): void
    {
        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectExceptionObject(new AppByNameNotFoundException('test-app'));
        } else {
            $this->expectException(AppNotFoundException::class);
        }

        $this->controller->runAction(new RequestDataBag(['appName' => 'test-app']), $this->context);
    }

    public function testRunActionThrowsAppByNameNotFoundExceptionWhenAppSecretIsNull(): void
    {
        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectException(MissingAppSecretException::class);
            $this->expectExceptionMessage('Failed to retrieve app secret.');
        } else {
            $this->expectException(AppException::class);
            $this->expectExceptionMessage(AppException::appSecretMissing('test-app')->getMessage());
        }

        $entity = $this->buildAppEntity('test-app', null, []);
        $this->assertEntityRepositoryWithEntity($entity);

        $this->controller->runAction(new RequestDataBag(['appName' => $entity->getName()]), $this->context);
    }

    public function testRunActionThrowsUnallowedHostExceptionWhenTargetHostIsEmpty(): void
    {
        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectExceptionObject(new UnallowedHostException('', [], 'test-app'));
        } else {
            $this->expectException(AppException::class);
            $this->expectExceptionMessage(AppException::hostNotAllowed('', 'test-app')->getMessage());
        }

        $entity = $this->buildAppEntity('test-app', 'test-secrets', []);
        $this->assertEntityRepositoryWithEntity($entity);

        $this->controller->runAction(new RequestDataBag(['appName' => $entity->getName()]), $this->context);
    }

    public function testRunActionThrowsUnallowedHostExceptionWhenTargetHostIsNotAllowed(): void
    {
        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectExceptionObject(new UnallowedHostException('test-host', ['cicada'], 'test-app'));
        } else {
            $this->expectException(AppException::class);
            $this->expectExceptionMessage(AppException::hostNotAllowed('test-host', 'test-app')->getMessage());
        }

        $entity = $this->buildAppEntity('test-app', 'test-secrets', ['cicada']);
        $this->assertEntityRepositoryWithEntity($entity);

        $this->controller->runAction(
            new RequestDataBag(['appName' => $entity->getName(), 'url' => 'test-host']),
            $this->context
        );
    }

    public function testRunActionThrowsInvalidArgumentExceptionWhenNoIdInRequestBag(): void
    {
        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectExceptionObject(new \InvalidArgumentException('Ids must be an array'));
        } else {
            $this->expectException(AppException::class);
            $this->expectExceptionMessage(AppException::invalidArgument('Ids must be an array')->getMessage());
        }

        $entity = $this->buildAppEntity('test-app', 'test-secrets', ['foo.bar']);
        $this->assertEntityRepositoryWithEntity($entity);

        $this->controller->runAction(
            new RequestDataBag(['appName' => $entity->getName(), 'url' => 'https://foo.bar/test']),
            $this->context
        );
    }

    public function testRunActionExecutesAnAppAction(): void
    {
        $entity = $this->buildAppEntity('test-app', 'test-secrets', ['foo.bar']);
        $this->assertEntityRepositoryWithEntity($entity);

        $this->appPayloadServiceHelper->expects(static::once())->method('buildSource')->with('1.0.0', $entity->getName());
        $this->executor->expects(static::once())->method('execute');

        $this->controller->runAction(
            new RequestDataBag([
                'appName' => $entity->getName(),
                'url' => 'https://foo.bar',
                'ids' => [Uuid::randomHex()],
                'entity' => 'app',
                'action' => 'do-nothing',
            ]),
            $this->context,
        );
    }

    public function testSignUriThrowsAppByNameNotFoundExceptionWhenAppIsNotFound(): void
    {
        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectExceptionObject(new AppByNameNotFoundException('test-app'));
        } else {
            $this->expectException(AppNotFoundException::class);
        }

        $this->controller->signUri(new RequestDataBag(['appName' => 'test-app']), $this->context);
    }

    public function testSignUriReturnsJsonResponseWithUri(): void
    {
        $entity = $this->buildAppEntity('test-app', 'test-secrets', ['foo.bar']);
        $this->assertEntityRepositoryWithEntity($entity);

        $requestBag = new RequestDataBag(['appName' => $entity->getName(), 'uri' => 'test-uri']);

        $this->querySigner->expects(static::once())->method('signUri')
            ->with($requestBag->get('uri'), $entity, $this->context)
            ->willReturn($this->createMock(UriInterface::class));

        $response = $this->controller->signUri($requestBag, $this->context);

        static::assertNotFalse($response->getContent());
        static::assertJsonStringEqualsJsonString('{"uri":""}', $response->getContent());
    }

    protected function assertEntityRepositoryWithEntity(AppEntity $entity): void
    {
        $collection = new EntityCollection();
        $collection->add($entity);
        $collection->add($this->buildAppEntity('secondAppDiscarded', null, []));

        $this->entityRepository->expects(static::once())->method('search')
            ->willReturn(
                new EntitySearchResult(
                    'app',
                    2,
                    $collection,
                    null,
                    new Criteria(),
                    $this->context
                )
            );
    }

    /**
     * @param list<string>|null $allowedHosts
     */
    protected function buildAppEntity(string $name, ?string $appSecret, ?array $allowedHosts): AppEntity
    {
        $entity = new AppEntity();
        $entity->setId(Uuid::randomHex());
        $entity->setUniqueIdentifier(Uuid::randomHex());
        $entity->setName($name);
        $entity->setVersion('1.0.0');
        $entity->setAppSecret($appSecret);
        $entity->setAllowedHosts($allowedHosts);

        return $entity;
    }
}
