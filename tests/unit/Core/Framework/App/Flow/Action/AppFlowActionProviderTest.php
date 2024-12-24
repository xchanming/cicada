<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Flow\Action;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Content\Flow\Dispatching\FlowFactory;
use Cicada\Core\Content\Flow\Dispatching\Storer\OrderStorer;
use Cicada\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Cicada\Core\Framework\App\Flow\Action\AppFlowActionProvider;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Webhook\BusinessEventEncoder;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(AppFlowActionProvider::class)]
class AppFlowActionProviderTest extends TestCase
{
    public function testGetWebhookPayloadAndHeaders(): void
    {
        $params = [
            ['name' => 'param1', 'type' => 'string', 'value' => '{{ config1 }}'],
            ['name' => 'param2', 'type' => 'string', 'value' => '{{ config2 }} and {{ config3 }}'],
        ];

        $headers = [
            ['name' => 'content-type', 'type' => 'string', 'value' => 'application/json'],
        ];

        $config = [
            'config1' => 'Text 1',
            'config2' => 'Text 2',
            'config3' => 'Text 3',
        ];

        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())
            ->method('fetchAssociative')
            ->willReturn(
                ['parameters' => json_encode($params), 'headers' => json_encode($headers)]
            );

        $ids = new IdsCollection();
        $order = new OrderEntity();
        $order->setId($ids->get('orderId'));

        $entitySearchResult = $this->createMock(EntitySearchResult::class);
        $entitySearchResult->expects(static::once())
            ->method('get')
            ->willReturn($order);

        $orderRepo = $this->createMock(EntityRepository::class);
        $orderRepo->expects(static::once())
            ->method('search')
            ->willReturn($entitySearchResult);

        $context = Generator::createSalesChannelContext();

        $awareEvent = new CheckoutOrderPlacedEvent($context, $order);

        $orderStorer = new OrderStorer($orderRepo, $this->createMock(EventDispatcherInterface::class));

        $flow = (new FlowFactory([$orderStorer]))->create($awareEvent);
        $flow->setConfig($config);

        $stringTemplateRender = $this->createMock(StringTemplateRenderer::class);
        $stringTemplateRender->expects(static::exactly(6))
            ->method('render')
            ->willReturnOnConsecutiveCalls(
                'Text 1',
                'Text 2',
                'Text 3',
                'Text 1',
                'Text 2 and Text 3',
                'application/json'
            );

        $appFlowActionProvider = new AppFlowActionProvider(
            $connection,
            $this->createMock(BusinessEventEncoder::class),
            $stringTemplateRender
        );

        $webhookData = $appFlowActionProvider->getWebhookPayloadAndHeaders($flow, $ids->get('appFlowActionId'));

        static::assertEquals(['param1' => 'Text 1', 'param2' => 'Text 2 and Text 3'], $webhookData['payload']);
        static::assertEquals(['content-type' => 'application/json'], $webhookData['headers']);
    }
}
