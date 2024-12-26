<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Flow;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Document\DocumentCollection;
use Cicada\Core\Checkout\Document\DocumentEntity;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\DeliveryNoteRenderer;
use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Content\ContactForm\Event\ContactFormEvent;
use Cicada\Core\Content\Flow\Dispatching\Action\SendMailAction;
use Cicada\Core\Content\Flow\Dispatching\FlowFactory;
use Cicada\Core\Content\Flow\Events\FlowSendMailActionEvent;
use Cicada\Core\Content\Mail\Service\MailAttachmentsBuilder;
use Cicada\Core\Content\Mail\Service\MailFactory;
use Cicada\Core\Content\Mail\Service\MailService;
use Cicada\Core\Content\Mail\Transport\MailerTransportDecorator;
use Cicada\Core\Content\MailTemplate\Exception\MailEventConfigurationException;
use Cicada\Core\Content\MailTemplate\MailTemplateCollection;
use Cicada\Core\Content\MailTemplate\MailTemplateEntity;
use Cicada\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\System\Locale\LanguageLocaleCodeProvider;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * @internal
 */
#[Package('services-settings')]
class SendMailActionTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<OrderCollection>
     */
    private EntityRepository $orderRepository;

    private Connection $connection;

    /**
     * @var EntityRepository<DocumentCollection>
     */
    private EntityRepository $documentRepository;

    /**
     * @var EntityRepository<MailTemplateCollection>
     */
    private EntityRepository $mailTemplateRepository;

    protected function setUp(): void
    {
        $this->orderRepository = static::getContainer()->get('order.repository');
        $this->connection = static::getContainer()->get(Connection::class);
        $this->documentRepository = static::getContainer()->get('document.repository');
        $this->mailTemplateRepository = static::getContainer()->get('mail_template.repository');
    }

    /**
     * @param array<string>|null $documentTypeIds
     * @param array<string, mixed> $recipients
     */
    #[DataProvider('sendMailProvider')]
    public function testEmailSend(array $recipients, ?array $documentTypeIds = [], ?bool $hasOrderSettingAttachment = true): void
    {
        $documentRepository = static::getContainer()->get('document.repository');
        $orderRepository = static::getContainer()->get('order.repository');

        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Generator::createSalesChannelContext();

        $customerId = $this->createCustomer($context->getContext());
        $orderId = $this->createOrder($customerId, $context->getContext());

        $mailTemplateId = $this->retrieveMailTemplateId();

        $config = array_filter([
            'mailTemplateId' => $mailTemplateId,
            'recipient' => $recipients,
            'documentTypeIds' => $documentTypeIds,
        ]);

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions.stateMachineState');
        /** @var OrderEntity $order */
        $order = $orderRepository->search($criteria, $context->getContext())->first();
        $event = new CheckoutOrderPlacedEvent($context, $order);

        $documentIdOlder = null;
        $documentIdNewer = null;
        $documentIds = [];

        if ($documentTypeIds !== null && $documentTypeIds !== [] || $hasOrderSettingAttachment) {
            $documentIdOlder = $this->createDocumentWithFile($orderId, $context->getContext());
            $documentIdNewer = $this->createDocumentWithFile($orderId, $context->getContext());
            $documentIds[] = $documentIdNewer;
        }

        if ($hasOrderSettingAttachment) {
            $event->getContext()->addExtension(
                SendMailAction::MAIL_CONFIG_EXTENSION,
                new MailSendSubscriberConfig(
                    false,
                    $documentIds,
                )
            );
        }

        $transportDecorator = new MailerTransportDecorator(
            $this->createMock(TransportInterface::class),
            static::getContainer()->get(MailAttachmentsBuilder::class),
            static::getContainer()->get('cicada.filesystem.public'),
            static::getContainer()->get('document.repository')
        );
        $mailService = new TestEmailService(static::getContainer()->get(MailFactory::class), $transportDecorator);
        $subscriber = new SendMailAction(
            $mailService,
            static::getContainer()->get('mail_template.repository'),
            static::getContainer()->get('logger'),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get('mail_template_type.repository'),
            static::getContainer()->get(Translator::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            true
        );

        $mailFilterEvent = null;
        static::getContainer()->get('event_dispatcher')->addListener(FlowSendMailActionEvent::class, static function ($event) use (&$mailFilterEvent): void {
            $mailFilterEvent = $event;
        });

        static::assertIsString($documentIdNewer);
        static::assertIsString($documentIdOlder);
        $criteria = new Criteria(array_filter([$documentIdOlder, $documentIdNewer]));
        $documents = $documentRepository->search($criteria, $context->getContext());

        $newDocument = $documents->get($documentIdNewer);
        static::assertNotNull($newDocument);
        static::assertInstanceOf(DocumentEntity::class, $newDocument);
        static::assertFalse($newDocument->getSent());
        $newDocumentOrderVersionId = $newDocument->getOrderVersionId();

        $oldDocument = $documents->get($documentIdOlder);
        static::assertInstanceOf(DocumentEntity::class, $oldDocument);
        static::assertFalse($oldDocument->getSent());
        $oldDocumentOrderVersionId = $oldDocument->getOrderVersionId();

        // new version is created
        static::assertNotEquals($newDocumentOrderVersionId, Defaults::LIVE_VERSION);
        static::assertNotEquals($oldDocumentOrderVersionId, Defaults::LIVE_VERSION);

        $flowFactory = static::getContainer()->get(FlowFactory::class);
        $flow = $flowFactory->create($event);
        $flow->setConfig($config);

        $subscriber->handleFlow($flow);

        static::assertInstanceOf(FlowSendMailActionEvent::class, $mailFilterEvent);
        static::assertEquals(1, $mailService->calls);
        static::assertIsArray($mailService->data);
        static::assertArrayHasKey('recipients', $mailService->data);

        switch ($recipients['type']) {
            case 'admin':
                $admin = static::getContainer()->get(Connection::class)->fetchAssociative(
                    'SELECT `name`,`email` FROM `user` WHERE `admin` = 1'
                );
                static::assertIsArray($admin);
                static::assertEquals($mailService->data['recipients'], [$admin['email'] => $admin['name']]);

                break;
            case 'custom':
                static::assertEquals($mailService->data['recipients'], $recipients['data']);

                break;
            default:
                static::assertEquals($mailService->data['recipients'], [$order->getOrderCustomer()?->getEmail() => $order->getOrderCustomer()?->getName()]);
        }

        if ($documentTypeIds !== null && $documentTypeIds !== []) {
            $criteria = new Criteria(array_filter([$documentIdOlder, $documentIdNewer]));
            $documents = $documentRepository->search($criteria, $context->getContext());

            $newDocument = $documents->get($documentIdNewer);
            static::assertNotNull($newDocument);
            static::assertInstanceOf(DocumentEntity::class, $newDocument);
            static::assertTrue($newDocument->getSent());

            $oldDocument = $documents->get($documentIdOlder);
            static::assertNotNull($oldDocument);
            static::assertInstanceOf(DocumentEntity::class, $oldDocument);
            static::assertFalse($oldDocument->getSent());

            // new document with new version id, old document with old version id
            static::assertEquals($newDocumentOrderVersionId, $newDocument->getOrderVersionId());
            static::assertEquals($oldDocumentOrderVersionId, $oldDocument->getOrderVersionId());
        }
    }

    /**
     * @return iterable<string, mixed>
     */
    public static function sendMailProvider(): iterable
    {
        yield 'Test send mail default' => [['type' => 'customer']];
        yield 'Test send mail admin' => [['type' => 'admin']];
        yield 'Test send mail custom' => [[
            'type' => 'custom',
            'data' => [
                'test2@example.com' => 'Overwrite',
            ],
        ]];
        yield 'Test send mail without attachments' => [['type' => 'customer'], []];
        yield 'Test send mail with attachments from order setting' => [['type' => 'customer'], [], true];
        yield 'Test send mail with attachments from order setting and flow setting ' => [
            ['type' => 'customer'],
            [self::getDocIdByType(DeliveryNoteRenderer::TYPE)],
            true,
        ];
    }

    public function testUpdateMailTemplateTypeWithMailTemplateTypeIdIsNull(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Context::createDefaultContext();

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, [], []));

        $mailTemplateId = $this->retrieveMailTemplateId();

        static::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE mail_template SET mail_template_type_id = null WHERE id =:id',
            [
                'id' => Uuid::fromHexToBytes($mailTemplateId),
            ]
        );

        static::assertNotEmpty($mailTemplateId);

        $config = [
            'mailTemplateId' => $mailTemplateId,
            'recipient' => [
                'type' => 'admin',
                'data' => [
                    'phuoc.cao@xchanming.com' => 'cicada',
                    'phuoc.cao.x@xchanming.com' => 'cicada',
                ],
            ],
        ];

        $event = new ContactFormEvent($context, TestDefaults::SALES_CHANNEL, new MailRecipientStruct(['test@example.com' => 'Cicada ag']), new DataBag());

        $mailService = new TestEmailService();
        $subscriber = new SendMailAction(
            $mailService,
            static::getContainer()->get('mail_template.repository'),
            static::getContainer()->get('logger'),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get('mail_template_type.repository'),
            static::getContainer()->get(Translator::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            true
        );

        $mailFilterEvent = null;
        static::getContainer()->get('event_dispatcher')->addListener(FlowSendMailActionEvent::class, static function ($event) use (&$mailFilterEvent): void {
            $mailFilterEvent = $event;
        });

        $flowFactory = static::getContainer()->get(FlowFactory::class);
        $flow = $flowFactory->create($event);
        $flow->setConfig($config);

        $subscriber->handleFlow($flow);

        static::assertIsObject($mailFilterEvent);
        static::assertEquals(1, $mailService->calls);
    }

    #[DataProvider('sendMailContactFormProvider')]
    public function testSendContactFormMail(bool $hasEmail, bool $hasName): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Context::createDefaultContext();

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, [], []));

        $mailTemplateId = $this->retrieveMailTemplateId();

        static::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE mail_template SET mail_template_type_id = null WHERE id =:id',
            [
                'id' => Uuid::fromHexToBytes($mailTemplateId),
            ]
        );

        static::assertNotEmpty($mailTemplateId);

        $config = [
            'mailTemplateId' => $mailTemplateId,
            'recipient' => [
                'type' => 'contactFormMail',
            ],
        ];
        $data = new DataBag();
        if ($hasEmail) {
            $data->set('email', 'test@example.com');
        }
        if ($hasName) {
            $data->set('name', 'Cicada');
        }

        $event = new ContactFormEvent($context, TestDefaults::SALES_CHANNEL, new MailRecipientStruct(['test2@example.com' => 'Cicada ag 2']), $data);

        $mailService = new TestEmailService();
        $subscriber = new SendMailAction(
            $mailService,
            static::getContainer()->get('mail_template.repository'),
            static::getContainer()->get('logger'),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get('mail_template_type.repository'),
            static::getContainer()->get(Translator::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            true
        );

        $mailFilterEvent = null;
        static::getContainer()->get('event_dispatcher')->addListener(FlowSendMailActionEvent::class, static function ($event) use (&$mailFilterEvent): void {
            $mailFilterEvent = $event;
        });

        $flowFactory = static::getContainer()->get(FlowFactory::class);
        $flow = $flowFactory->create($event);
        $flow->setConfig($config);

        $subscriber->handleFlow($flow);

        if ($hasEmail) {
            static::assertIsArray($mailService->data);
            static::assertArrayHasKey('recipients', $mailService->data);
            static::assertIsObject($mailFilterEvent);
            static::assertEquals(1, $mailService->calls);
            static::assertEquals([$data->get('email') => $data->get('name')], $mailService->data['recipients']);
        } else {
            static::assertIsNotObject($mailFilterEvent);
            static::assertEquals(0, $mailService->calls);
        }
    }

    public function testSendContactFormMailType(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Generator::createSalesChannelContext();

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, [], []));

        $mailTemplateId = $this->retrieveMailTemplateId();

        static::getContainer()->get(Connection::class)->executeStatement(
            'UPDATE mail_template SET mail_template_type_id = null WHERE id =:id',
            [
                'id' => Uuid::fromHexToBytes($mailTemplateId),
            ]
        );

        static::assertNotEmpty($mailTemplateId);

        $config = [
            'mailTemplateId' => $mailTemplateId,
            'recipient' => [
                'type' => 'contactFormMail',
            ],
        ];

        $customerId = $this->createCustomer($context->getContext());
        $orderId = $this->createOrder($customerId, $context->getContext());
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('orderCustomer');

        $order = static::getContainer()->get('order.repository')->search($criteria, $context->getContext())->get($orderId);
        static::assertInstanceOf(OrderEntity::class, $order);
        $event = new CheckoutOrderPlacedEvent($context, $order);

        $mailService = new TestEmailService();
        $subscriber = new SendMailAction(
            $mailService,
            static::getContainer()->get('mail_template.repository'),
            static::getContainer()->get('logger'),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get('mail_template_type.repository'),
            static::getContainer()->get(Translator::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            true
        );

        $mailFilterEvent = null;
        static::getContainer()->get('event_dispatcher')->addListener(FlowSendMailActionEvent::class, static function ($event) use (&$mailFilterEvent): void {
            $mailFilterEvent = $event;
        });

        $flowFactory = static::getContainer()->get(FlowFactory::class);
        $flow = $flowFactory->create($event);
        $flow->setConfig($config);

        $subscriber->handleFlow($flow);

        static::assertIsNotObject($mailFilterEvent);
        static::assertEquals(0, $mailService->calls);
    }

    /**
     * @return iterable<string, array<int, bool>>
     */
    public static function sendMailContactFormProvider(): iterable
    {
        yield 'Test send mail has data valid' => [true, true, true];
        yield 'Test send mail contact form without email' => [false, true, true];
        yield 'Test send mail contact form without firstName' => [true, false, true];
        yield 'Test send mail contact form without lastName' => [true, false, true];
    }

    public function testSendMailWithConfigIsNull(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Context::createDefaultContext();

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, [], []));

        $mailTemplateId = $this->retrieveMailTemplateId();

        $config = array_filter([
            'mailTemplateId' => $mailTemplateId,
            'recipient' => [],
        ]);

        $event = new ContactFormEvent($context, TestDefaults::SALES_CHANNEL, new MailRecipientStruct(['test@example.com' => 'Cicada ag']), new DataBag());

        $mailService = new TestEmailService();
        $subscriber = new SendMailAction(
            $mailService,
            static::getContainer()->get('mail_template.repository'),
            static::getContainer()->get('logger'),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get('mail_template_type.repository'),
            static::getContainer()->get(Translator::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            true
        );

        $mailFilterEvent = null;
        static::getContainer()->get('event_dispatcher')->addListener(FlowSendMailActionEvent::class, static function ($event) use (&$mailFilterEvent): void {
            $mailFilterEvent = $event;
        });

        static::expectException(MailEventConfigurationException::class);
        static::expectExceptionMessage('The recipient value in the flow action configuration is missing.');

        $flowFactory = static::getContainer()->get(FlowFactory::class);
        $flow = $flowFactory->create($event);
        $flow->setConfig($config);

        $subscriber->handleFlow($flow);

        static::assertIsObject($mailFilterEvent);
        static::assertEquals(1, $mailService->calls);
    }

    #[DataProvider('updateTemplateDataProvider')]
    public function testUpdateTemplateData(bool $shouldUpdate): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Context::createDefaultContext();

        $mailTemplate = static::getContainer()
            ->get('mail_template.repository')
            ->search($criteria, $context)
            ->first();

        static::getContainer()->get(Connection::class)->executeStatement('UPDATE mail_template_type SET template_data = NULL');

        static::assertInstanceOf(MailTemplateEntity::class, $mailTemplate);

        $config = array_filter([
            'mailTemplateId' => $mailTemplate->getId(),
            'recipient' => [
                'type' => 'admin',
                'data' => [
                    'phuoc.cao@xchanming.com' => 'cicada',
                    'phuoc.cao.x@xchanming.com' => 'cicada',
                ],
            ],
        ]);

        $event = new ContactFormEvent($context, TestDefaults::SALES_CHANNEL, new MailRecipientStruct(['test@example.com' => 'Cicada ag']), new DataBag());

        $mailService = new TestEmailService();

        $subscriber = new SendMailAction(
            $mailService,
            static::getContainer()->get('mail_template.repository'),
            static::getContainer()->get('logger'),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get('mail_template_type.repository'),
            static::getContainer()->get(Translator::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            $shouldUpdate
        );

        $mailFilterEvent = null;
        static::getContainer()->get('event_dispatcher')->addListener(FlowSendMailActionEvent::class, static function ($event) use (&$mailFilterEvent): void {
            $mailFilterEvent = $event;
        });

        $flowFactory = static::getContainer()->get(FlowFactory::class);
        $flow = $flowFactory->create($event);
        $flow->setConfig($config);

        $subscriber->handleFlow($flow);

        static::assertIsObject($mailFilterEvent);
        static::assertEquals(1, $mailService->calls);
        static::assertNotNull($mailTemplate->getMailTemplateTypeId());
        $data = static::getContainer()->get(Connection::class)->fetchOne(
            'SELECT template_data FROM mail_template_type WHERE id = :id',
            ['id' => Uuid::fromHexToBytes($mailTemplate->getMailTemplateTypeId())]
        );

        if ($shouldUpdate) {
            static::assertNotNull($data);
        } else {
            static::assertNull($data);
        }
    }

    public static function updateTemplateDataProvider(): \Generator
    {
        yield 'Test disable mail template updates' => [false];
        yield 'Test enable mail template updates' => [true];
    }

    public function testTranslatorInjectionInMail(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Context::createDefaultContext();

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, [], []));

        $mailTemplateId = $this->retrieveMailTemplateId();

        $config = array_filter([
            'mailTemplateId' => $mailTemplateId,
            'recipient' => [
                'type' => 'admin',
                'data' => [
                    'phuoc.cao@xchanming.com' => 'cicada',
                    'phuoc.cao.x@xchanming.com' => 'cicada',
                ],
            ],
        ]);

        $event = new ContactFormEvent($context, TestDefaults::SALES_CHANNEL, new MailRecipientStruct(['test@example.com' => 'Cicada ag']), new DataBag());
        $translator = static::getContainer()->get(Translator::class);

        $mailService = new TestEmailService();
        $subscriber = new SendMailAction(
            $mailService,
            static::getContainer()->get('mail_template.repository'),
            static::getContainer()->get('logger'),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get('mail_template_type.repository'),
            $translator,
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(LanguageLocaleCodeProvider::class),
            true
        );

        $mailFilterEvent = null;
        $snippetSetId = null;
        $function = static function ($event) use (&$mailFilterEvent, $translator, &$snippetSetId): void {
            $mailFilterEvent = $event;
            $snippetSetId = $translator->getSnippetSetId();
        };

        static::getContainer()->get('event_dispatcher')->addListener(FlowSendMailActionEvent::class, $function);

        $flowFactory = static::getContainer()->get(FlowFactory::class);
        $flow = $flowFactory->create($event);
        $flow->setConfig($config);

        $subscriber->handleFlow($flow);

        static::assertIsObject($mailFilterEvent);
        static::assertEmpty($translator->getSnippetSetId());
        static::assertNotNull($snippetSetId);
    }

    public function testNumberOfDocumentAttachmentsInCaseFlowSequencesAttachDifferentDocuments(): void
    {
        $context = Context::createDefaultContext();
        $customerId = $this->createCustomer($context);
        $orderId = $this->createOrder($customerId, $context);
        $order = $this->orderRepository->search(new Criteria([$orderId]), $context)->getEntities()->first();
        static::assertInstanceOf(OrderEntity::class, $order);

        $documentTypes = $this->connection->fetchAllAssociative(
            'SELECT HEX(`id`) AS `id`, `technical_name` FROM document_type WHERE `technical_name` IN (:type1, :type2);',
            [
                'type1' => InvoiceRenderer::TYPE,
                'type2' => DeliveryNoteRenderer::TYPE,
            ]
        );
        static::assertCount(2, $documentTypes);

        foreach ($documentTypes as $index => $documentType) {
            $generatedDocumentId = $this->createDocumentWithFile($orderId, $context, $documentType['technical_name']);
            $documentTypes[$index]['documentId'] = $generatedDocumentId;

            $criteria = new Criteria([$generatedDocumentId]);
            $criteria->addAssociation('documentMediaFile');
            $document = $this->documentRepository->search($criteria, $context)->getEntities()->first();
            static::assertInstanceOf(DocumentEntity::class, $document);

            $documentMediaFile = $document->getDocumentMediaFile();
            static::assertInstanceOf(MediaEntity::class, $documentMediaFile);
            $documentTypes[$index]['filename'] = $documentMediaFile->getFileName() . '.' . $documentMediaFile->getFileExtension();
        }

        $mailTemplateId = $this->retrieveMailTemplateId();

        $context->addExtension(
            SendMailAction::MAIL_CONFIG_EXTENSION,
            new MailSendSubscriberConfig(
                false,
                [],
                []
            )
        );

        $event = new OrderStateMachineStateChangeEvent('state_enter.order.state.in_progress', $order, $context);
        $flowFactory = static::getContainer()->get(FlowFactory::class);
        $flow = $flowFactory->create($event);

        $sequencesConfig = $this->createFlowSequencesConfig($mailTemplateId, $documentTypes);

        foreach ($sequencesConfig as $config) {
            $flow->setConfig($config);

            $transportDecorator = new MailerTransportDecorator(
                $this->createMock(TransportInterface::class),
                static::getContainer()->get(MailAttachmentsBuilder::class),
                static::getContainer()->get('cicada.filesystem.public'),
                $this->documentRepository
            );

            $mailService = new TestEmailService(static::getContainer()->get(MailFactory::class), $transportDecorator);

            $sendMailAction = new SendMailAction(
                $mailService,
                $this->mailTemplateRepository,
                static::getContainer()->get('logger'),
                static::getContainer()->get('event_dispatcher'),
                static::getContainer()->get('mail_template_type.repository'),
                static::getContainer()->get(Translator::class),
                $this->connection,
                static::getContainer()->get(LanguageLocaleCodeProvider::class),
                true
            );

            $sendMailAction->handleFlow($flow);

            static::assertInstanceOf(Email::class, $mailService->mail);
            $attachments = $mailService->mail->getAttachments();

            static::assertCount(\count($config['documentTypeIds']), $attachments);

            foreach ($config['documentTypeIds'] as $sequenzDocumentTypeId) {
                $documentInfos = $this->getMatchingDocument($sequenzDocumentTypeId, $documentTypes);
                static::assertNotEmpty($documentInfos);

                $found = $this->isDocumentPartOfAttachments($attachments, $documentInfos['filename']);
                static::assertTrue($found, 'Attachment not found for document type: ' . $documentInfos['technical_name']);

                $markedAsSent = $this->isDocumentMarkedAsSent($documentInfos['documentId'], $context);
                static::assertTrue($markedAsSent, 'Successfully sent document with id ' . $documentInfos['documentId'] . ' was not marked as sent.');
            }
        }
    }

    private function createCustomer(Context $context): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'username' => 'Mustermann',
            'nickname' => 'Mustermann',
            'customerNumber' => '1337',
            'email' => Uuid::randomHex() . '@example.com',
            'password' => '12345678',
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        static::getContainer()
            ->get('customer.repository')
            ->upsert([$customer], $context);

        return $customerId;
    }

    private function createOrder(string $customerId, Context $context): string
    {
        $orderId = Uuid::randomHex();
        $stateId = static::getContainer()->get(InitialStateIdLoader::class)->get(OrderStates::STATE_MACHINE);
        $billingAddressId = Uuid::randomHex();

        $order = [
            'id' => $orderId,
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'orderNumber' => Uuid::randomHex(),
            'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'orderCustomer' => [
                'customerId' => $customerId,
                'email' => 'test@example.com',
                'salutationId' => $this->getValidSalutationId(),
                'name' => 'Max',
            ],
            'stateId' => $stateId,
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1.0,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'billingAddressId' => $billingAddressId,
            'addresses' => [
                [
                    'id' => $billingAddressId,
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                    'countryId' => $this->getValidCountryId(),
                ],
            ],
            'lineItems' => [],
            'deliveries' => [
            ],
            'transactions' => [
                [
                    'paymentMethodId' => $this->getValidPaymentMethodId(),
                    'stateId' => $stateId,
                    'amount' => new CalculatedPrice(200, 200, new CalculatedTaxCollection(), new TaxRuleCollection()),
                ],
            ],
            'context' => '{}',
            'payload' => '{}',
        ];

        $this->orderRepository->upsert([$order], $context);

        return $orderId;
    }

    private function createDocumentWithFile(string $orderId, Context $context, string $documentType = InvoiceRenderer::TYPE): string
    {
        $documentGenerator = static::getContainer()->get(DocumentGenerator::class);

        $operation = new DocumentGenerateOperation($orderId, FileTypes::PDF, []);
        /** @var DocumentEntity $document */
        $document = $documentGenerator->generate($documentType, [$orderId => $operation], $context)->getSuccess()->first();

        static::assertNotNull($document);

        return $document->getId();
    }

    private static function getDocIdByType(string $documentType): ?string
    {
        $document = KernelLifecycleManager::getConnection()->fetchFirstColumn(
            'SELECT LOWER(HEX(`id`)) FROM `document_type` WHERE `technical_name` = :documentType',
            [
                'documentType' => $documentType,
            ]
        );

        return $document !== [] ? $document[0] : '';
    }

    private function retrieveMailTemplateId(): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $id = $this->mailTemplateRepository
            ->searchIds($criteria, Context::createDefaultContext())
            ->firstId();

        static::assertIsString($id);

        return $id;
    }

    /**
     * @param array<int, array<string, string>> $documentTypes
     *
     * @return array<array{mailTemplateId: string, documentTypeIds: array<int, string>, recipient: array<string, string|array<string, string>>}>
     */
    private function createFlowSequencesConfig(string $mailTemplateId, array $documentTypes): array
    {
        return [
            [
                'mailTemplateId' => $mailTemplateId,
                'documentTypeIds' => [
                    $documentTypes[0]['id'],
                    $documentTypes[1]['id'],
                ],
                'recipient' => [
                    'type' => 'custom',
                    'data' => [
                        'first@test.com' => 'first recipient',
                    ],
                ],
            ],
            [
                'mailTemplateId' => $mailTemplateId,
                'documentTypeIds' => [
                    $documentTypes[0]['id'],
                ],
                'recipient' => [
                    'type' => 'custom',
                    'data' => [
                        'second@test.com' => 'second recipient',
                    ],
                ],
            ],
            [
                'mailTemplateId' => $mailTemplateId,
                'documentTypeIds' => [
                    $documentTypes[1]['id'],
                ],
                'recipient' => [
                    'type' => 'custom',
                    'data' => [
                        'third@test.com' => 'third recipient',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<array<string, string>> $documentTypes
     *
     * @return array{id: string, technical_name: string, documentId: string, filename: string}|array{}
     */
    private function getMatchingDocument(string $sequenzDocumentTypeId, array $documentTypes): array
    {
        foreach ($documentTypes as $documentType) {
            if ($documentType['id'] === $sequenzDocumentTypeId) {
                return $documentType;
            }
        }

        return [];
    }

    /**
     * @param array<DataPart> $attachments
     */
    private function isDocumentPartOfAttachments(array $attachments, string $documentName): bool
    {
        foreach ($attachments as $attachment) {
            if ($attachment->getFilename() === $documentName) {
                return true;
            }
        }

        return false;
    }

    private function isDocumentMarkedAsSent(string $documentId, Context $context): bool
    {
        $document = $this->documentRepository->search(new Criteria([$documentId]), $context)->getEntities()->first();
        static::assertInstanceOf(DocumentEntity::class, $document);

        return $document->getSent();
    }
}

/**
 * @internal
 */
#[Package('services-settings')]
class TestEmailService extends MailService
{
    public float $calls = 0;

    public ?Email $mail = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = null;

    public function __construct(
        private readonly ?MailFactory $mailFactory = null,
        private readonly ?MailerTransportDecorator $decorator = null
    ) {
    }

    public function send(array $data, Context $context, array $templateData = []): ?Email
    {
        $this->data = $data;
        ++$this->calls;

        if ($this->mailFactory && $this->decorator) {
            $mail = $this->mailFactory->create(
                $data['subject'],
                ['foo@example.com' => 'foobar'],
                $data['recipients'],
                [],
                [],
                $data,
                $data['binAttachments'] ?? null
            );
            $this->decorator->send($mail);

            $this->mail = $mail;

            return $mail;
        }

        return null;
    }
}
