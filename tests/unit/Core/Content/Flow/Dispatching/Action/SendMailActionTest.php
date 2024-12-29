<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Content\Flow\Dispatching\Action\FlowMailVariables;
use Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction;
use Cicada\Core\Content\Flow\Dispatching\FlowState;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\Struct\Sequence;
use Cicada\Core\Content\Mail\Service\AbstractMailService;
use Cicada\Core\Content\Mail\Service\MailAttachmentsConfig;
use Cicada\Core\Content\MailTemplate\Exception\MailEventConfigurationException;
use Cicada\Core\Content\MailTemplate\MailTemplateCollection;
use Cicada\Core\Content\MailTemplate\MailTemplateEntity;
use Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Event\LanguageAware;
use Cicada\Core\Framework\Event\MailAware;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(SendMailAction::class)]
class SendMailActionTest extends TestCase
{
    private MailTemplateEntity $mailTemplate;

    /**
     * @var AbstractMailService&MockObject
     */
    private AbstractMailService $mailService;

    /**
     * @var EntityRepository&MockObject
     */
    private EntityRepository $mailTemplateRepository;

    /**
     * @var EntityRepository&MockObject
     */
    private EntityRepository $mailTemplateTypeRepository;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var LanguageLocaleCodeProvider&MockObject
     */
    private LanguageLocaleCodeProvider $languageLocaleProvider;

    /**
     * @var AbstractTranslator&MockObject
     */
    private AbstractTranslator $translator;

    /**
     * @var EntitySearchResult<MailTemplateCollection>&MockObject
     */
    private EntitySearchResult $entitySearchResult;

    private SendMailAction $action;

    protected function setUp(): void
    {
        $this->mailTemplate = new MailTemplateEntity();
        $this->mailService = $this->createMock(AbstractMailService::class);
        $this->mailTemplateRepository = $this->createMock(EntityRepository::class);
        $this->languageLocaleProvider = $this->createMock(LanguageLocaleCodeProvider::class);
        $this->translator = $this->createMock(Translator::class);
        $this->entitySearchResult = $this->createMock(EntitySearchResult::class);
        $this->mailTemplateTypeRepository = $this->createMock(EntityRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->action = new SendMailAction(
            $this->mailService,
            $this->mailTemplateRepository,
            $this->logger,
            $this->createMock(EventDispatcherInterface::class),
            $this->mailTemplateTypeRepository,
            $this->translator,
            $this->createMock(Connection::class),
            $this->languageLocaleProvider,
            true
        );
    }

    public function testRequirements(): void
    {
        static::assertSame(
            [MailAware::class],
            $this->action->requirements()
        );
    }

    public function testName(): void
    {
        static::assertSame('action.mail.send', SendMailAction::getName());
    }

    #[DataProvider('mailTemplateTypeProvider')]
    public function testUpdateMailTemplateType(MailTemplateTypeUpdateProvider $provider): void
    {
        $context = Context::createDefaultContext();

        $connection = $this->createMock(Connection::class);

        $action = new SendMailAction(
            $this->mailService,
            $this->mailTemplateRepository,
            $this->logger,
            $this->createMock(EventDispatcherInterface::class),
            $this->mailTemplateTypeRepository,
            $this->translator,
            $connection,
            $this->languageLocaleProvider,
            $provider->updateMailTemplateTypeParam
        );

        $mailTemplateId = Uuid::randomHex();
        $this->mailTemplate->setId($mailTemplateId);
        $this->mailTemplate->setSenderName('Phuoc');
        $config = array_filter([
            'mailTemplateId' => $mailTemplateId,
            'recipient' => ['type' => 'customer'],
            'documentTypeIds' => null,
            'replyTo' => 'foo@example.com',
        ]);

        $this->mailTemplate->setMailTemplateTypeId($provider->mailTemplateTypeId);

        $expected = [
            'data' => [
                'recipients' => [
                    'email' => 'name',
                ],
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'templateId' => $mailTemplateId,
            ],
            'context' => $context,
        ];

        $templateData = new MailRecipientStruct($expected['data']['recipients']);

        $flow = new StorableFlow(
            '',
            $expected['context'],
            []
        );
        $state = new FlowState();
        $state->currentSequence = new Sequence();
        $state->currentSequence->sequenceId = Uuid::randomHex();
        $state->currentSequence->flowId = Uuid::randomHex();
        $state->flowId = $state->currentSequence->flowId;
        $flow->setFlowState($state);
        $flow->setData(MailAware::MAIL_STRUCT, $templateData);
        $flow->setData(MailAware::SALES_CHANNEL_ID, TestDefaults::SALES_CHANNEL);

        $flow->setConfig($config);

        $this->entitySearchResult->expects(static::once())
            ->method('first')
            ->willReturn($this->mailTemplate);

        $this->mailTemplateRepository->expects(static::once())
            ->method('search')
            ->willReturn($this->entitySearchResult);

        if (!$provider->updateMailTemplateTypeParam) {
            $connection->expects(static::never())->method('fetchOne');
            $this->logger->expects(static::never())->method('warning');
            $action->handleFlow($flow);

            return;
        }

        if (!$provider->mailTemplateTypeId) {
            $connection->expects(static::never())->method('fetchOne');
            $this->logger->expects(static::never())->method('warning');
            $action->handleFlow($flow);

            return;
        }

        if (!$provider->mailTemplateTypeTranslationExists) {
            $connection->expects(static::once())->method('fetchOne')->willReturn(false);

            $this->logger->expects(static::once())->method('warning')->with(
                "Could not update mail template type, because translation for this language does not exits:\n"
                . 'Flow id: ' . $flow->getFlowState()->flowId . "\n"
                . 'Sequence id: ' . $flow->getFlowState()->getSequenceId()
            );
            $action->handleFlow($flow);

            return;
        }

        if ($provider->expectUpdateMailTemplateType) {
            $connection->expects(static::once())
                ->method('fetchOne')
                ->willReturn(true);

            $this->mailTemplateTypeRepository->expects(static::once())->method('update')->with([
                [
                    'id' => $provider->mailTemplateTypeId,
                    'templateData' => [
                        'mailStruct' => $templateData,
                        'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    ],
                ],
            ], $context);
            $this->logger->expects(static::never())->method('warning');
        } else {
            $this->mailTemplateTypeRepository->expects(static::never())->method('update');
        }

        $action->handleFlow($flow);
    }

    /**
     * @param array<string, string> $exptectedReplyTo
     */
    #[DataProvider('replyToProvider')]
    public function testActionExecuted(?string $replyTo, array $exptectedReplyTo = []): void
    {
        $orderId = Uuid::randomHex();
        $mailTemplateId = Uuid::randomHex();
        $this->mailTemplate->setId($mailTemplateId);
        $this->mailTemplate->setSenderName('Phuoc');
        $config = array_filter([
            'mailTemplateId' => $mailTemplateId,
            'recipient' => ['type' => 'customer'],
            'documentTypeIds' => null,
            'replyTo' => $replyTo,
        ]);

        $expected = [
            'data' => [
                'recipients' => [
                    'email' => 'name',
                ],
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'templateId' => $mailTemplateId,
                'customFields' => null,
                'contentHtml' => null,
                'contentPlain' => null,
                'subject' => null,
                'mediaIds' => [],
                'senderName' => null,
                'languageId' => null,
                'attachmentsConfig' => new MailAttachmentsConfig(
                    Context::createDefaultContext(),
                    $this->mailTemplate,
                    new MailSendSubscriberConfig(false, []),
                    $config,
                    $orderId
                ),
            ],
            'context' => Context::createDefaultContext(),
        ];

        $templateData = new MailRecipientStruct($expected['data']['recipients']);

        $expected['data'] = array_merge($expected['data'], $exptectedReplyTo);

        $flow = new StorableFlow(
            '',
            $expected['context'],
            [
                MailAware::MAIL_STRUCT => [
                    'recipients' => [
                        'email' => 'name',
                    ],
                ],
                MailAware::SALES_CHANNEL_ID => TestDefaults::SALES_CHANNEL,
                OrderAware::ORDER_ID => $orderId,
            ]
        );
        $flow->setData(MailAware::MAIL_STRUCT, $templateData);
        $flow->setData(MailAware::SALES_CHANNEL_ID, TestDefaults::SALES_CHANNEL);
        $flow->setData(OrderAware::ORDER_ID, $orderId);
        $flow->setData(FlowMailVariables::CONTACT_FORM_DATA, [
            'email' => 'customer@example.com',
            'name' => 'Max',
        ]);

        $flow->setConfig($config);

        $this->entitySearchResult->expects(static::once())
            ->method('first')
            ->willReturn($this->mailTemplate);

        $this->mailTemplateRepository->expects(static::once())
            ->method('search')
            ->willReturn($this->entitySearchResult);

        $this->translator->expects(static::once())
            ->method('getSnippetSetId')
            ->willReturn(null);

        $this->languageLocaleProvider->expects(static::once())
            ->method('getLocaleForLanguageId')
            ->willReturn('en-GB');

        $this->mailService->expects(static::once())
            ->method('send')
            ->with(
                $expected['data'],
                $expected['context'],
                [
                    'eventName' => $flow->getName(),
                    'mailStruct' => $templateData,
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'orderId' => $orderId,
                    'contactFormData' => [
                        'email' => 'customer@example.com',
                        'name' => 'Max',
                    ],
                ],
            );

        $this->action->handleFlow($flow);
    }

    /**
     * @return iterable<string, array<MailTemplateTypeUpdateProvider>>
     */
    public static function mailTemplateTypeProvider(): iterable
    {
        yield 'mailTemplateTypeUpdate param is false' => [new MailTemplateTypeUpdateProvider(
            updateMailTemplateTypeParam: false,
            mailTemplateTypeId: Uuid::randomHex(),
            mailTemplateTypeTranslationExists: false,
            expectUpdateMailTemplateType: false
        )];

        yield 'no mail template type id' => [new MailTemplateTypeUpdateProvider(
            updateMailTemplateTypeParam: true,
            mailTemplateTypeId: null,
            mailTemplateTypeTranslationExists: true,
            expectUpdateMailTemplateType: false
        )];

        yield 'no mail template translation exists' => [new MailTemplateTypeUpdateProvider(
            updateMailTemplateTypeParam: true,
            mailTemplateTypeId: Uuid::randomHex(),
            mailTemplateTypeTranslationExists: false,
            expectUpdateMailTemplateType: false
        )];

        yield 'mail template translation exists' => [new MailTemplateTypeUpdateProvider(
            updateMailTemplateTypeParam: true,
            mailTemplateTypeId: Uuid::randomHex(),
            mailTemplateTypeTranslationExists: true,
            expectUpdateMailTemplateType: true
        )];
    }

    public static function replyToProvider(): \Generator
    {
        yield 'no reply to' => [null];
        yield 'custom reply to' => ['foo@example.com', ['senderMail' => 'foo@example.com']];
        yield 'contact form reply to' => ['contactFormMail', [
            'senderMail' => 'customer@example.com',
            'senderName' => '{% if contactFormData.name is defined %}{{ contactFormData.name }}{% endif %}',
        ]];
    }

    public function testActionWithNotAware(): void
    {
        $flow = new StorableFlow('', Context::createDefaultContext(), []);
        $flow->setConfig(array_filter([
            'mailTemplateId' => Uuid::randomHex(),
            'recipient' => ['type' => 'customer'],
            'documentTypeIds' => null,
            'replyTo' => '',
        ]));

        static::expectException(MailEventConfigurationException::class);
        $this->mailService->expects(static::never())->method('send');

        $this->action->handleFlow($flow);
    }

    public function testActionWithEmptyConfig(): void
    {
        $flow = new StorableFlow('', Context::createDefaultContext(), []);

        static::expectException(MailEventConfigurationException::class);
        $this->mailService->expects(static::never())->method('send');

        static::expectException(MailEventConfigurationException::class);
        $this->mailService->expects(static::never())->method('send');

        $this->action->handleFlow($flow);
    }

    public function testActionExecutedWithRecipientFromStoreData(): void
    {
        $mailTemplateId = Uuid::randomHex();
        $orderId = Uuid::randomHex();
        $config = array_filter([
            'mailTemplateId' => $mailTemplateId,
            'recipient' => ['type' => 'customer'],
            'documentTypeIds' => null,
        ]);
        $languageId = Uuid::randomHex();

        $expected = [
            'data' => [
                'recipients' => [
                    'email' => 'name',
                ],
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'templateId' => $mailTemplateId,
                'customFields' => null,
                'contentHtml' => null,
                'contentPlain' => null,
                'subject' => null,
                'mediaIds' => [],
                'senderName' => null,
                'languageId' => $languageId,
                'attachmentsConfig' => new MailAttachmentsConfig(
                    Context::createDefaultContext(),
                    $this->mailTemplate,
                    new MailSendSubscriberConfig(false, []),
                    $config,
                    $orderId
                ),
            ],
            'context' => Context::createDefaultContext(),
        ];

        $templateData = new MailRecipientStruct($expected['data']['recipients']);
        $this->mailTemplate->setId($mailTemplateId);

        $flow = new StorableFlow(
            '',
            $expected['context'],
            [
                MailAware::MAIL_STRUCT => [
                    'recipients' => [
                        'email' => 'name',
                    ],
                ],
                MailAware::SALES_CHANNEL_ID => TestDefaults::SALES_CHANNEL,
                OrderAware::ORDER_ID => $orderId,
            ]
        );
        $flow->setData(MailAware::MAIL_STRUCT, $templateData);
        $flow->setData(MailAware::SALES_CHANNEL_ID, TestDefaults::SALES_CHANNEL);
        $flow->setData(OrderAware::ORDER_ID, $orderId);
        $flow->setData(LanguageAware::LANGUAGE_ID, $languageId);
        $flow->setData(FlowMailVariables::CONTACT_FORM_DATA, [
            'email' => 'customer@example.com',
            'name' => 'Max',
        ]);

        $flow->setConfig($config);

        $this->entitySearchResult->expects(static::once())
            ->method('first')
            ->willReturn($this->mailTemplate);

        $this->mailTemplateRepository->expects(static::once())
            ->method('search')
            ->willReturn($this->entitySearchResult);

        $this->translator->expects(static::once())
            ->method('getSnippetSetId')
            ->willReturn(null);

        $this->languageLocaleProvider->expects(static::once())
            ->method('getLocaleForLanguageId')
            ->willReturn('en-GB');

        $this->mailService->expects(static::once())
            ->method('send')
            ->with(
                $expected['data'],
                $expected['context'],
                [
                    'mailStruct' => $templateData,
                    'eventName' => '',
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'orderId' => $orderId,
                    'contactFormData' => [
                        'email' => 'customer@example.com',
                        'name' => 'Max',
                    ],
                    'languageId' => $languageId,
                ]
            );

        $this->action->handleFlow($flow);
    }
}

/**
 * @internal
 */
class MailTemplateTypeUpdateProvider
{
    /**
     * @internal
     */
    public function __construct(
        public readonly bool $updateMailTemplateTypeParam,
        public readonly ?string $mailTemplateTypeId,
        public readonly bool $mailTemplateTypeTranslationExists,
        public readonly bool $expectUpdateMailTemplateType
    ) {
    }
}
