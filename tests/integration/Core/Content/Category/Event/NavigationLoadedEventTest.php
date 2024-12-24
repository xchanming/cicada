<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Category\Event;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Category\Event\NavigationLoadedEvent;
use Cicada\Core\Content\Category\Service\NavigationLoader;
use Cicada\Core\Content\Category\Service\NavigationLoaderInterface;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
class NavigationLoadedEventTest extends TestCase
{
    use IntegrationTestBehaviour;

    protected NavigationLoaderInterface $loader;

    protected function setUp(): void
    {
        $this->loader = static::getContainer()->get(NavigationLoader::class);
        parent::setUp();
    }

    public function testEventDispatched(): void
    {
        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');

        $dispatcher = static::getContainer()->get('event_dispatcher');
        $this->addEventListener($dispatcher, NavigationLoadedEvent::class, $listener);

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $navigationId = $context->getSalesChannel()->getNavigationCategoryId();

        $this->loader->load($navigationId, $context, $navigationId);
    }
}
