<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Maintenance\Staging\Handler;

use Cicada\Core\Content\Mail\Service\MailSender;
use Cicada\Core\Framework\Context;
use Cicada\Core\Maintenance\Staging\Event\SetupStagingEvent;
use Cicada\Core\Maintenance\Staging\Handler\StagingMailHandler;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[CoversClass(StagingMailHandler::class)]
class StagingMailHandlerTest extends TestCase
{
    public function testDisabled(): void
    {
        $config = new StaticSystemConfigService();
        $handler = new StagingMailHandler(false, $config);

        $handler(new SetupStagingEvent(Context::createDefaultContext(), $this->createMock(SymfonyStyle::class)));

        static::assertNull($config->get(MailSender::DISABLE_MAIL_DELIVERY));
    }

    public function testEnabled(): void
    {
        $config = new StaticSystemConfigService();
        $handler = new StagingMailHandler(true, $config);

        $handler(new SetupStagingEvent(Context::createDefaultContext(), $this->createMock(SymfonyStyle::class)));

        static::assertTrue($config->get(MailSender::DISABLE_MAIL_DELIVERY));
    }
}
