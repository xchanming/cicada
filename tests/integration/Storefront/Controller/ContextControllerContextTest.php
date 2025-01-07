<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Event\SalesChannelContextSwitchEvent;
use Cicada\Storefront\Framework\Routing\Router;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class ContextControllerContextTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    private KernelBrowser $browser;

    private string $testBaseUrl;

    private string $defaultBaseUrl;

    private string $languageId;

    private Router $router;

    protected function setUp(): void
    {
        $this->router = static::getContainer()->get('router');

        $this->languageId = Uuid::randomHex();
        $localeId = Uuid::randomHex();

        $this->defaultBaseUrl = $_SERVER['APP_URL'];
        $this->testBaseUrl = $_SERVER['APP_URL'] . '/tst-TST';

        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM sales_channel');

        $domains = [
            [
                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => $this->defaultBaseUrl,
            ],
            [
                'language' => [
                    'id' => $this->languageId,
                    'name' => 'Test',
                    'locale' => [
                        'id' => $localeId,
                        'name' => 'Test',
                        'code' => 'af_ZA',
                        'territory' => 'test',
                    ],
                    'translationCodeId' => $localeId,
                ],
                'currencyId' => Defaults::CURRENCY,
                'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                'url' => $this->testBaseUrl,
            ],
        ];

        $this->browser = $this->createCustomSalesChannelBrowser([
            'domains' => $domains,
            'languages' => [['id' => Defaults::LANGUAGE_SYSTEM], ['id' => $this->languageId]],
        ]);
    }

    protected function tearDown(): void
    {
        $this->router->getContext()->setBaseUrl('');
    }

    public function testSwitchToUpperCasePath(): void
    {
        $this->browser->request('GET', $this->defaultBaseUrl);
        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $this->browser->request(
            'POST',
            $this->defaultBaseUrl . '/checkout/language',
            ['languageId' => $this->languageId]
        );

        $response = $this->browser->getResponse();
        static::assertSame(302, $response->getStatusCode(), $response->getContent() ?: '');
        static::assertSame($this->testBaseUrl . '/', $response->headers->get('Location'));
    }

    public function testSwitchFromUpperCasePath(): void
    {
        $this->browser->request('GET', $this->testBaseUrl);
        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $this->browser->request(
            'POST',
            $this->testBaseUrl . '/checkout/language',
            ['languageId' => Defaults::LANGUAGE_SYSTEM]
        );

        $response = $this->browser->getResponse();
        static::assertSame(302, $response->getStatusCode(), $response->getContent() ?: '');
        static::assertSame($this->defaultBaseUrl . '/', $response->headers->get('Location'));
    }

    public function testSwitchWithWrongRedirectTo(): void
    {
        $this->browser->request('GET', $this->testBaseUrl);
        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $this->browser->request(
            'POST',
            $this->testBaseUrl . '/checkout/language',
            ['languageId' => Defaults::LANGUAGE_SYSTEM, 'redirectTo' => 'frontend.homer.page']
        );

        $response = $this->browser->getResponse();
        static::assertSame(302, $response->getStatusCode(), $response->getContent() ?: '');
        static::assertSame($this->defaultBaseUrl . '/', $response->headers->get('Location'));
    }

    public function testSwitchWithProductIdAndCorrectRedirectTo(): void
    {
        $this->browser->request('GET', $this->testBaseUrl);
        static::assertSame(200, $this->browser->getResponse()->getStatusCode(), $this->browser->getResponse()->getContent() ?: '');

        $productId = Uuid::randomHex();

        $this->browser->request(
            'POST',
            $this->testBaseUrl . '/checkout/language',
            ['languageId' => Defaults::LANGUAGE_SYSTEM, 'redirectTo' => 'frontend.detail.page', 'redirectParameters' => ['productId' => $productId]]
        );

        $response = $this->browser->getResponse();
        static::assertSame(302, $response->getStatusCode(), $response->getContent() ?: '');
        static::assertSame($this->defaultBaseUrl . '/detail/' . $productId, $response->headers->get('Location'));
    }

    public function testConfigure(): void
    {
        $this->browser->request('GET', $this->testBaseUrl);
        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $contextSubscriber = new ContextControllerTestSubscriber();
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber($contextSubscriber);

        $this->browser->request(
            'POST',
            $this->testBaseUrl . '/checkout/configure',
            ['languageId' => $this->languageId]
        );

        $response = $this->browser->getResponse();

        $dispatcher->removeSubscriber($contextSubscriber);

        static::assertSame(200, $response->getStatusCode(), $response->getContent() ?: '');
        static::assertSame($this->languageId, $contextSubscriber->switchEvent->getRequestDataBag()->get('languageId'));
    }
}

/**
 * @internal
 */
class ContextControllerTestSubscriber implements EventSubscriberInterface
{
    public SalesChannelContextSwitchEvent $switchEvent;

    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelContextSwitchEvent::class => 'onSwitch',
        ];
    }

    public function onSwitch(SalesChannelContextSwitchEvent $event): void
    {
        $this->switchEvent = $event;
    }
}
