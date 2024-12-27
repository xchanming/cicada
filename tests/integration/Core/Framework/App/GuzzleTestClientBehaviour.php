<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\Integration\App\GuzzleHistoryCollector;
use Cicada\Core\Test\Integration\App\TestAppServer;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\PromiseInterface;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
trait GuzzleTestClientBehaviour
{
    use IntegrationTestBehaviour;

    #[Before]
    #[After]
    public function resetHistory(): void
    {
        $historyCollector = static::getContainer()->get(GuzzleHistoryCollector::class);
        static::assertInstanceOf(GuzzleHistoryCollector::class, $historyCollector);
        $historyCollector->resetHistory();
        $mockHandler = static::getContainer()->get(MockHandler::class);
        static::assertInstanceOf(MockHandler::class, $mockHandler);
        $mockHandler->reset();
        $testServer = static::getContainer()->get(TestAppServer::class);
        static::assertInstanceOf(TestAppServer::class, $testServer);
        $testServer->reset();
    }

    public function getLastRequest(): ?RequestInterface
    {
        $mockHandler = static::getContainer()->get(MockHandler::class);
        static::assertInstanceOf(MockHandler::class, $mockHandler);

        return $mockHandler->getLastRequest();
    }

    public function getPastRequest(int $index): RequestInterface
    {
        $historyCollector = static::getContainer()->get(GuzzleHistoryCollector::class);
        static::assertInstanceOf(GuzzleHistoryCollector::class, $historyCollector);

        return $historyCollector->getHistory()[$index]['request'];
    }

    public function getRequestCount(): int
    {
        $historyCollector = static::getContainer()->get(GuzzleHistoryCollector::class);
        static::assertInstanceOf(GuzzleHistoryCollector::class, $historyCollector);

        return \count($historyCollector->getHistory());
    }

    public function appendNewResponse(ResponseInterface|\Exception|PromiseInterface $response): void
    {
        $mockHandler = static::getContainer()->get(MockHandler::class);
        static::assertInstanceOf(MockHandler::class, $mockHandler);
        $mockHandler->append($response);
    }

    public function didRegisterApp(): bool
    {
        $testServer = static::getContainer()->get(TestAppServer::class);
        static::assertInstanceOf(TestAppServer::class, $testServer);

        return $testServer->didRegister();
    }
}
