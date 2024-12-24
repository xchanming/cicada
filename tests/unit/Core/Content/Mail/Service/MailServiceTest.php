<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Mail\Service;

use Cicada\Core\Content\Mail\Service\AbstractMailFactory;
use Cicada\Core\Content\Mail\Service\AbstractMailSender;
use Cicada\Core\Content\Mail\Service\MailService;
use Cicada\Core\Content\MailTemplate\Exception\SalesChannelNotFoundException;
use Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeSentEvent;
use Cicada\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent;
use Cicada\Core\Content\MailTemplate\Service\Event\MailErrorEvent;
use Cicada\Core\Content\MailTemplate\Service\Event\MailSentEvent;
use Cicada\Core\Framework\Adapter\Twig\StringTemplateRenderer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
#[CoversClass(MailService::class)]
class MailServiceTest extends TestCase
{
    /**
     * @var MockObject&StringTemplateRenderer
     */
    private StringTemplateRenderer $templateRenderer;

    /**
     * @var MockObject&AbstractMailFactory
     */
    private AbstractMailFactory $mailFactory;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private EventDispatcherInterface $eventDispatcher;

    private MailService $mailService;

    /**
     * @var MockObject&EntityRepository
     */
    private EntityRepository $salesChannelRepository;

    /**
     * @var MockObject&LoggerInterface
     */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->mailFactory = $this->createMock(AbstractMailFactory::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->templateRenderer = $this->createMock(StringTemplateRenderer::class);
        $this->salesChannelRepository = $this->createMock(EntityRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->mailService = new MailService(
            $this->createMock(DataValidator::class),
            $this->templateRenderer,
            $this->mailFactory,
            $this->createMock(AbstractMailSender::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(SalesChannelDefinition::class),
            $this->salesChannelRepository,
            $this->createMock(SystemConfigService::class),
            $this->eventDispatcher,
            $this->logger,
        );
    }

    public function testThrowSalesChannelNotFound(): void
    {
        $salesChannelId = Uuid::randomHex();
        $exception = new SalesChannelNotFoundException($salesChannelId);
        static::expectExceptionObject($exception);

        $data = [
            'recipients' => [],
            'salesChannelId' => $salesChannelId,
        ];

        $this->mailService->send($data, Context::createDefaultContext());
    }

    public function testSendMailSuccess(): void
    {
        $salesChannelId = Uuid::randomHex();

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId($salesChannelId);
        $context = Context::createDefaultContext();

        $salesChannelResult = new EntitySearchResult(
            'sales_channel',
            1,
            new SalesChannelCollection([$salesChannel]),
            null,
            new Criteria(),
            $context
        );

        $this->salesChannelRepository->expects(static::once())->method('search')->willReturn($salesChannelResult);

        $data = [
            'recipients' => [],
            'senderName' => 'me',
            'senderEmail' => 'me@cicada.com',
            'subject' => 'Test email',
            'contentPlain' => 'Content plain',
            'contentHtml' => 'Content html',
            'salesChannelId' => $salesChannelId,
        ];

        $email = (new Email())->subject($data['subject'])
            ->html($data['contentHtml'])
            ->text($data['contentPlain'])
            ->to('me@cicada.com')
            ->from(new Address($data['senderEmail']));

        $this->mailFactory->expects(static::once())->method('create')->willReturn($email);
        $this->templateRenderer->expects(static::exactly(4))->method('render')->willReturn('');
        $this->eventDispatcher->expects(static::exactly(3))->method('dispatch')->willReturnOnConsecutiveCalls(
            static::isInstanceOf(MailBeforeValidateEvent::class),
            static::isInstanceOf(MailBeforeSentEvent::class),
            static::isInstanceOf(MailSentEvent::class)
        );
        $email = $this->mailService->send($data, Context::createDefaultContext());

        static::assertInstanceOf(Email::class, $email);
    }

    public function testSendMailWithRenderingError(): void
    {
        $salesChannelId = Uuid::randomHex();

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId($salesChannelId);
        $context = Context::createDefaultContext();

        $salesChannelResult = new EntitySearchResult(
            'sales_channel',
            1,
            new SalesChannelCollection([$salesChannel]),
            null,
            new Criteria(),
            $context
        );

        $this->salesChannelRepository->expects(static::once())->method('search')->willReturn($salesChannelResult);

        $data = [
            'recipients' => [],
            'senderName' => 'me',
            'senderEmail' => 'me@cicada.com',
            'subject' => 'Test email',
            'contentPlain' => 'Content plain',
            'contentHtml' => 'Content html',
            'salesChannelId' => $salesChannelId,
        ];

        $email = (new Email())->subject($data['subject'])
            ->html($data['contentHtml'])
            ->text($data['contentPlain'])
            ->to($data['senderEmail'])
            ->from(new Address($data['senderEmail']));

        $this->mailFactory->expects(static::never())->method('create')->willReturn($email);
        $beforeValidateEvent = null;
        $mailErrorEvent = null;

        $this->logger->expects(static::once())->method('warning');
        $this->eventDispatcher->expects(static::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (Event $event) use (&$beforeValidateEvent, &$mailErrorEvent) {
                if ($event instanceof MailBeforeValidateEvent) {
                    $beforeValidateEvent = $event;

                    return $event;
                }

                $mailErrorEvent = $event;

                return $event;
            });

        $this->templateRenderer->expects(static::exactly(1))->method('render')->willThrowException(new \Exception('cannot render'));

        $email = $this->mailService->send($data, Context::createDefaultContext());

        static::assertNull($email);
        static::assertNotNull($beforeValidateEvent);
        static::assertInstanceOf(MailErrorEvent::class, $mailErrorEvent);
        static::assertEquals(Level::Warning, $mailErrorEvent->getLogLevel());
        static::assertNotNull($mailErrorEvent->getMessage());

        $message = 'Could not render Mail-Template with error message: cannot render';

        static::assertSame($message, $mailErrorEvent->getMessage());
        static::assertSame('Test email', $mailErrorEvent->getTemplate());
        static::assertSame([
            'salesChannel' => $salesChannel,
        ], $mailErrorEvent->getTemplateData());
    }

    public function testSendMailWithoutSenderName(): void
    {
        $salesChannelId = Uuid::randomHex();

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId($salesChannelId);
        $context = Context::createDefaultContext();

        $salesChannelResult = new EntitySearchResult(
            'sales_channel',
            1,
            new SalesChannelCollection([$salesChannel]),
            null,
            new Criteria(),
            $context
        );

        $this->salesChannelRepository->expects(static::once())->method('search')->willReturn($salesChannelResult);

        $data = [
            'recipients' => [],
            'subject' => 'Test email',
            'senderName' => 'me@cicada.com',
            'contentPlain' => 'Content plain',
            'contentHtml' => 'Content html',
            'salesChannelId' => $salesChannelId,
        ];

        $this->logger->expects(static::once())->method('error');
        $this->eventDispatcher->expects(static::exactly(4))->method('dispatch')->willReturnOnConsecutiveCalls(
            static::isInstanceOf(MailBeforeValidateEvent::class),
            static::isInstanceOf(MailErrorEvent::class),
            static::isInstanceOf(MailBeforeSentEvent::class),
            static::isInstanceOf(MailSentEvent::class)
        );

        $email = (new Email())->subject($data['subject'])
            ->html($data['contentHtml'])
            ->text($data['contentPlain'])
            ->to('test@cicada.com')
            ->from(new Address('test@cicada.com'));

        $this->mailFactory->expects(static::once())->method('create')->willReturn($email);

        $email = $this->mailService->send($data, Context::createDefaultContext());

        static::assertInstanceOf(Email::class, $email);
    }
}
