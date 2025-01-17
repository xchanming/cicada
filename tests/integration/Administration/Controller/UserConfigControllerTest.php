<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Administration\Controller;

use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Api\OAuth\Scope\UserVerifiedScope;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Group('slow')]
class UserConfigControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    protected function setUp(): void
    {
        $this->authorizeBrowser($this->getBrowser(), [UserVerifiedScope::IDENTIFIER], []);
    }

    protected function tearDown(): void
    {
        $this->resetBrowser();
    }

    public function testGetConfigMe(): void
    {
        $configKey = 'me.read';

        static::getContainer()->get('user_config.repository')
            ->create([[
                'userId' => $this->getUserId(),
                'key' => $configKey,
                'value' => ['content'],
            ]], Context::createDefaultContext());

        $this->getBrowser()->request('GET', '/api/_info/config-me', ['keys' => [$configKey]]);
        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode(), $content);
        static::assertSame([$configKey => ['content']], json_decode($content, true, 512, \JSON_THROW_ON_ERROR)['data']);
    }

    public function testGetAllConfigMe(): void
    {
        $configKey = 'me.read';

        static::getContainer()->get('user_config.repository')
            ->create([[
                'userId' => $this->getUserId(),
                'key' => $configKey,
                'value' => ['content'],
            ]], Context::createDefaultContext());

        $this->getBrowser()->request('GET', '/api/_info/config-me');
        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode(), $content);
        static::assertSame([$configKey => ['content']], json_decode($content, true, 512, \JSON_THROW_ON_ERROR)['data']);
    }

    public function testGetNullConfigMe(): void
    {
        $configKey = 'me.config';
        $ids = new IdsCollection();

        // Different user
        $user = [
            'id' => $ids->get('user'),
            'email' => 'foo@bar.com',
            'name' => 'Firstname',
            'password' => TestDefaults::HASHED_PASSWORD,
            'phone' => (string) rand(10000000000, 99999999999),
            'username' => 'foobar',
            'localeId' => static::getContainer()->get(Connection::class)->fetchOne('SELECT LOWER(HEX(id)) FROM locale LIMIT 1'),
            'aclRoles' => [],
        ];

        static::getContainer()->get('user.repository')->create([$user], Context::createDefaultContext());

        static::getContainer()->get('user_config.repository')
            ->create([[
                'userId' => $ids->get('user'),
                'key' => $configKey,
                'value' => ['content'],
            ]], Context::createDefaultContext());

        $this->getBrowser()->request('GET', '/api/_info/config-me', ['keys' => [$configKey]]);
        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode(), $content);
        static::assertSame([], json_decode($content, true, 512, \JSON_THROW_ON_ERROR)['data']);

        // Different Key
        $userId = $this->getUserId();

        static::getContainer()->get('user_config.repository')
            ->create([[
                'userId' => $userId,
                'key' => $configKey,
                'value' => ['content'],
            ]], Context::createDefaultContext());
        $this->getBrowser()->request('GET', '/api/_info/config-me', ['keys' => ['random-key']]);
        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode(), $content);
        static::assertSame([], json_decode($content, true, 512, \JSON_THROW_ON_ERROR)['data']);
    }

    public function testUpdateConfigMe(): void
    {
        $configKey = 'me.config';
        $anotherConfigKey = 'random.key';
        $anotherValue = 'random.value';

        static::getContainer()->get('user_config.repository')
            ->create([[
                'userId' => $this->getUserId(),
                'key' => $configKey,
                'value' => ['content'],
            ]], Context::createDefaultContext());

        $newValue = 'another-content';
        $this->getBrowser()->request('POST', '/api/_info/config-me', [], [], [], json_encode([
            $configKey => [$newValue],
            $anotherConfigKey => [$anotherValue],
        ], \JSON_THROW_ON_ERROR));
        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $this->getBrowser()->request('GET', '/api/_info/config-me', ['keys' => [$configKey, $anotherConfigKey]]);
        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode(), $content);
        static::assertSame([
            $configKey => [$newValue],
            $anotherConfigKey => [$anotherValue],
        ], json_decode($content, true, 512, \JSON_THROW_ON_ERROR)['data']);
    }

    public function testCreateConfigMe(): void
    {
        $configKey = 'me.config';
        $newValue = 'another-content';
        $this->getBrowser()->request('POST', '/api/_info/config-me', [], [], [], json_encode([
            $configKey => [$newValue],
        ], \JSON_THROW_ON_ERROR));

        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $this->getBrowser()->request('GET', '/api/_info/config-me', ['keys' => [$configKey]]);
        $response = $this->getBrowser()->getResponse();
        $content = $response->getContent();
        static::assertIsString($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode(), $content);
        static::assertSame([$configKey => [$newValue]], json_decode($content, true, 512, \JSON_THROW_ON_ERROR)['data']);
    }

    public function testCreateWithSendingEmptyParameter(): void
    {
        $this->getBrowser()->request('POST', '/api/_info/config-me');
        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $this->getBrowser()->request('POST', '/api/_info/config-me', [], [], [], json_encode([], \JSON_THROW_ON_ERROR));
        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    private function getUserId(): string
    {
        $context = $this->getBrowser()->getServerParameter(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT);
        static::assertInstanceOf(Context::class, $context);
        $source = $context->getSource();
        static::assertInstanceOf(AdminApiSource::class, $source);
        $userId = $source->getUserId();
        static::assertIsString($userId);

        return Uuid::fromBytesToHex($userId);
    }
}
