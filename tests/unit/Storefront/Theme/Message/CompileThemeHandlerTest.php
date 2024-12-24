<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Message;

use Cicada\Administration\Notification\NotificationService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Theme\ConfigLoader\AbstractConfigLoader;
use Cicada\Storefront\Theme\Message\CompileThemeHandler;
use Cicada\Storefront\Theme\Message\CompileThemeMessage;
use Cicada\Storefront\Theme\StorefrontPluginRegistryInterface;
use Cicada\Storefront\Theme\ThemeCompiler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CompileThemeHandler::class)]
class CompileThemeHandlerTest extends TestCase
{
    public function testHandleMessageCompile(): void
    {
        $themeCompilerMock = $this->createMock(ThemeCompiler::class);
        $notificationServiceMock = $this->createMock(NotificationService::class);
        $themeId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $message = new CompileThemeMessage(TestDefaults::SALES_CHANNEL, $themeId, true, $context);

        $themeCompilerMock->expects(static::once())->method('compileTheme');

        $scEntity = new SalesChannelEntity();
        $scEntity->setUniqueIdentifier(Uuid::randomHex());
        $scEntity->setName('Test SalesChannel');

        /** @var StaticEntityRepository<EntityCollection<SalesChannelEntity>> $salesChannelRep */
        $salesChannelRep = new StaticEntityRepository([new EntityCollection([$scEntity])]);

        $handler = new CompileThemeHandler(
            $themeCompilerMock,
            $this->createMock(AbstractConfigLoader::class),
            $this->createMock(StorefrontPluginRegistryInterface::class),
            $notificationServiceMock,
            $salesChannelRep
        );

        $handler($message);
    }
}
