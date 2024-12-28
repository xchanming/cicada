<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ContactForm;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\LandingPage\LandingPageDefinition;
use Cicada\Core\Content\MailTemplate\Service\Event\MailSentEvent;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\MailTemplateTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[Group('store-api')]
class ContactFormRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MailTemplateTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
    }

    public function testContactFormSendMail(): void
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit): void {
            $eventDidRun = true;
            $phpunit->assertStringContainsString('Contact email address: test@xchanming.com', $event->getContents()['text/html']);
            $phpunit->assertStringContainsString('essage: Lorem ipsum dolor sit amet', $event->getContents()['text/html']);
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->browser
            ->request(
                'POST',
                '/store-api/contact-form',
                [
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Firstname',
                    'email' => 'test@shäpware.com',
                    'phone' => '12345/6789',
                    'subject' => 'Subject',
                    'comment' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('individualSuccessMessage', $response);
        static::assertEmpty($response['individualSuccessMessage']);

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
    }

    #[DataProvider('navigationProvider')]
    public function testContactFormSendMailWithNavigationIdAndSlotId(string $entityName): void
    {
        [$navigationId, $slotId] = match ($entityName) {
            LandingPageDefinition::ENTITY_NAME => $this->createLandingPageData(),
            ProductDefinition::ENTITY_NAME => $this->createProductData(),
            default => $this->createCategoryData(true),
        };

        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $phpunit = $this;
        $eventDidRun = false;
        $recipients = [];
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, &$recipients, $phpunit): void {
            $eventDidRun = true;
            $recipients = $event->getRecipients();
            $phpunit->assertStringContainsString('Contact email address: test@xchanming.com', $event->getContents()['text/html']);
            $phpunit->assertStringContainsString('essage: Lorem ipsum dolor sit amet', $event->getContents()['text/html']);
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->browser
            ->request(
                'POST',
                '/store-api/contact-form',
                [
                    'salutationId' => $this->getValidSalutationId(),
                    'navigationId' => $navigationId,
                    'slotId' => $slotId,
                    'entityName' => $entityName,
                    'name' => 'Firstname',
                    'email' => 'test@xchanming.com',
                    'phone' => '12345/6789',
                    'subject' => 'Subject',
                    'comment' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertArrayHasKey('individualSuccessMessage', $response);
        static::assertEmpty($response['individualSuccessMessage']);

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
        static::assertArrayHasKey('h.mac@example.com', $recipients);
    }

    public static function navigationProvider(): \Generator
    {
        yield 'Category with Slot Config' => [CategoryDefinition::ENTITY_NAME];
        yield 'Landing Page with Slot Config' => [LandingPageDefinition::ENTITY_NAME];
        yield 'Product Page with Slot Config' => [ProductDefinition::ENTITY_NAME];
    }

    public function testContactFormSendMailWithSlotId(): void
    {
        [$categoryId] = $this->createCategoryData();

        $formSlotId = $this->ids->create('form-slot');
        $this->createCmsFormData($formSlotId);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $phpunit = $this;
        $eventDidRun = false;
        $listenerClosure = function (MailSentEvent $event) use (&$eventDidRun, $phpunit): void {
            $eventDidRun = true;
            $phpunit->assertStringContainsString('Contact email address: test@xchanming.com', $event->getContents()['text/html']);
            $phpunit->assertStringContainsString('essage: Lorem ipsum dolor sit amet', $event->getContents()['text/html']);
        };

        $this->addEventListener($dispatcher, MailSentEvent::class, $listenerClosure);

        $this->browser
            ->request(
                'POST',
                '/store-api/contact-form',
                [
                    'salutationId' => $this->getValidSalutationId(),
                    'navigationId' => $categoryId,
                    'slotId' => $formSlotId,
                    'name' => 'Firstname',
                    'email' => 'test@xchanming.com',
                    'phone' => '12345/6789',
                    'subject' => 'Subject',
                    'comment' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('individualSuccessMessage', $response);
        static::assertEmpty($response['individualSuccessMessage']);

        $dispatcher->removeListener(MailSentEvent::class, $listenerClosure);

        static::assertTrue($eventDidRun, 'The mail.sent Event did not run');
    }

    #[DataProvider('contactFormWithDomainProvider')]
    public function testContactFormWithInvalid(string $name, \Closure $expectClosure): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/contact-form',
                [
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => $name,
                    'email' => 'test@xchanming.com',
                    'phone' => '12345/6789',
                    'subject' => 'Subject',
                    'comment' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $expectClosure($response);
    }

    public static function contactFormWithDomainProvider(): \Generator
    {
        yield 'subscribe with URL protocol HTTPS' => [
            'Y https://cicada.test',
            function (array $response): void {
                static::assertArrayHasKey('errors', $response);
                static::assertCount(1, $response['errors']);

                $errors = array_column(array_column($response['errors'], 'source'), 'pointer');

                static::assertContains('/name', $errors);
            },
        ];

        yield 'subscribe with URL protocol HTTP' => [
            'Y http://cicada.test',
            function (array $response): void {
                static::assertArrayHasKey('errors', $response);
                static::assertCount(1, $response['errors']);

                $errors = array_column(array_column($response['errors'], 'source'), 'pointer');

                static::assertContains('/name', $errors);
            },
        ];

        yield 'subscribe with URL localhost' => [
            'Y http://localhost:8080',
            function (array $response): void {
                static::assertArrayHasKey('errors', $response);
                static::assertCount(1, $response['errors']);

                $errors = array_column(array_column($response['errors'], 'source'), 'pointer');

                static::assertContains('/name', $errors);
            },
        ];
    }

    /**
     * @return array<int, string>
     */
    private function createCategoryData(bool $withSlotConfig = false): array
    {
        $contactCategoryId = $this->ids->get('contact-category-test');

        $slotId = $this->ids->create('form-slot');
        $slotConfig = $withSlotConfig ? [
            $slotId => [
                'mailReceiver' => [
                    'source' => 'static',
                    'value' => ['h.mac@example.com'],
                ],
                'confirmationText' => [
                    'source' => 'static',
                    'value' => '',
                ],
            ],
        ] : [];

        $data = [
            [
                'id' => $contactCategoryId,
                'translations' => [
                    [
                        'name' => 'EN-Entry',
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'slotConfig' => $slotConfig,
                    ],
                ],
            ],
        ];
        static::getContainer()->get('category.repository')->create($data, Context::createDefaultContext());

        return [$contactCategoryId, $slotId];
    }

    private function createCmsFormData(string $slotId): void
    {
        $cmsData = [
            [
                'id' => $this->ids->create('cms-page'),
                'name' => 'test page',
                'type' => 'landingpage',
                'sections' => [
                    [
                        'id' => $this->ids->create('section'),
                        'type' => 'default',
                        'position' => 0,
                        'blocks' => [
                            [
                                'type' => 'form',
                                'position' => 0,
                                'slots' => [
                                    [
                                        'id' => $slotId,
                                        'type' => 'form',
                                        'slot' => 'content',
                                        'config' => [
                                            'mailReceiver' => [
                                                'source' => 'static',
                                                'value' => ['h.mac@example.com'],
                                            ],
                                            'confirmationText' => [
                                                'source' => 'static',
                                                'value' => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        static::getContainer()->get('cms_page.repository')->create($cmsData, Context::createDefaultContext());
    }

    /**
     * @return array<int, string>
     */
    private function createLandingPageData(): array
    {
        $landingPageId = $this->ids->get('contact-landingpage-test');

        $slotId = $this->ids->create('form-slot');
        $slotConfig = [
            $slotId => [
                'mailReceiver' => [
                    'source' => 'static',
                    'value' => ['h.mac@example.com'],
                ],
                'confirmationText' => [
                    'source' => 'static',
                    'value' => '',
                ],
            ],
        ];

        $data = [
            [
                'id' => $landingPageId,
                'name' => Uuid::randomHex(),
                'url' => Uuid::randomHex(),
                'salesChannels' => [
                    ['id' => TestDefaults::SALES_CHANNEL],
                ],
                'slotConfig' => $slotConfig,
            ],
        ];
        static::getContainer()->get('landing_page.repository')->create($data, Context::createDefaultContext());

        return [$landingPageId, $slotId];
    }

    /**
     * @return array<int, string>
     */
    private function createProductData(): array
    {
        $productId = $this->ids->get('contact-product-test');

        $slotId = $this->ids->create('form-slot');
        $slotConfig = [
            $slotId => [
                'mailReceiver' => [
                    'source' => 'static',
                    'value' => ['h.mac@example.com'],
                ],
                'confirmationText' => [
                    'source' => 'static',
                    'value' => '',
                ],
            ],
        ];

        $data = [
            [
                'id' => $productId,
                'productNumber' => Uuid::randomHex(),
                'stock' => 1,
                'name' => 'Test Product',
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10.99, 'net' => 11.99, 'linked' => false]],
                'manufacturer' => ['name' => 'create'],
                'taxId' => $this->getValidTaxId(),
                'active' => true,
                'slotConfig' => $slotConfig,
            ],
        ];
        static::getContainer()->get('product.repository')->create($data, Context::createDefaultContext());

        return [$productId, $slotId];
    }
}
