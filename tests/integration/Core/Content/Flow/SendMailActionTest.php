<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Flow;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
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

    /**
     * @var EntityRepository<MailTemplateCollection>
     */
    private EntityRepository $mailTemplateRepository;

    protected function setUp(): void
    {
        $this->orderRepository = static::getContainer()->get('order.repository');
        $this->mailTemplateRepository = static::getContainer()->get('mail_template.repository');
    }

    /**
     * @param array<string>|null $documentTypeIds
     * @param array<string, mixed> $recipients
     */
    #[DataProvider('sendMailProvider')]
    public function testEmailSend(array $recipients, ?array $documentTypeIds = [], ?bool $hasOrderSettingAttachment = true): void
    {
        $orderRepository = static::getContainer()->get('order.repository');

        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Generator::generateSalesChannelContext();

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

        if ($hasOrderSettingAttachment) {
            $event->getContext()->addExtension(
                SendMailAction::MAIL_CONFIG_EXTENSION,
                new MailSendSubscriberConfig(
                    false
                )
            );
        }

        $transportDecorator = new MailerTransportDecorator(
            $this->createMock(TransportInterface::class),
            static::getContainer()->get(MailAttachmentsBuilder::class),
            static::getContainer()->get('cicada.filesystem.public'),
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
    }

    public function testUpdateMailTemplateTypeWithMailTemplateTypeIdIsNull(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Context::createDefaultContext();

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, []));

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

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, []));

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

        $context = Generator::generateSalesChannelContext();

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, []));

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
        yield 'Test send mail contact form without name' => [true, false, true];
    }

    public function testSendMailWithConfigIsNull(): void
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);

        $context = Context::createDefaultContext();

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, []));

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

        $context->addExtension(SendMailAction::MAIL_CONFIG_EXTENSION, new MailSendSubscriberConfig(false, []));

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

    private function createCustomer(Context $context): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
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
