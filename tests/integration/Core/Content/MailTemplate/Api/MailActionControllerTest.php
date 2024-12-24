<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\MailTemplate\Api;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Content\MailTemplate\Api\MailActionController;
use Cicada\Core\Content\MailTemplate\MailTemplateEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Serializer\JsonEntityEncoder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Serializer\StructNormalizer;
use Cicada\Core\Framework\Test\TestCaseBase\AdminApiTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(MailActionController::class)]
class MailActionControllerTest extends TestCase
{
    use AdminApiTestBehaviour;
    use IntegrationTestBehaviour;

    public function testSendSuccess(): void
    {
        $context = Context::createDefaultContext();

        $customerId = $this->createCustomer($context);
        $orderId = $this->createOrder($customerId, $context);

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('orderCustomer');
        $order = static::getContainer()->get('order.repository')->search($criteria, $context)->get($orderId);
        static::assertInstanceOf(OrderEntity::class, $order);

        $documentId = $this->createDocumentWithFile($orderId, $context);
        $documentIds = [$documentId];

        $criteria = new Criteria();
        $criteria->setLimit(1);
        /** @var ?MailTemplateEntity $mailTemplate */
        $mailTemplate = static::getContainer()
            ->get('mail_template.repository')
            ->search($criteria, $context)
            ->first();
        static::assertInstanceOf(MailTemplateEntity::class, $mailTemplate);

        $criteria = new Criteria([TestDefaults::SALES_CHANNEL]);
        $criteria->setLimit(1);
        $salesChannel = static::getContainer()
            ->get('sales_channel.repository')
            ->search($criteria, $context)
            ->first();
        static::assertInstanceOf(SalesChannelEntity::class, $salesChannel);

        $entityEncoder = new JsonEntityEncoder(
            new Serializer([new StructNormalizer()], [new JsonEncoder()])
        );
        $orderDefinition = static::getContainer()->get(OrderDefinition::class);
        $orderDecode = $entityEncoder->encode(new Criteria(), $orderDefinition, $order, '/api');
        array_walk_recursive($orderDecode, static function (&$value): void {
            if ($value instanceof \stdClass) {
                $value = json_decode((string) json_encode($value), true, 512, \JSON_THROW_ON_ERROR);
            }
        });

        $salesChannelDefinition = static::getContainer()->get(SalesChannelDefinition::class);
        $salesChannelDecode = $entityEncoder->encode(new Criteria(), $salesChannelDefinition, $salesChannel, '/api');
        array_walk_recursive($salesChannelDecode, static function (&$value): void {
            if ($value instanceof \stdClass) {
                $value = json_decode((string) json_encode($value), true, 512, \JSON_THROW_ON_ERROR);
            }
        });

        $this->getBrowser()
            ->request(
                'POST',
                '/api/_action/mail-template/send',
                [
                    'contentHtml' => $mailTemplate->getContentHtml(),
                    'contentPlain' => $mailTemplate->getContentPlain(),
                    'mailTemplateData' => [
                        'order' => $orderDecode,
                        'salesChannel' => $salesChannelDecode,
                    ],
                    'documentIds' => $documentIds,
                    'recipients' => ['d.dinh@cicada.com' => 'Duy'],
                    'salesChannelId' => $salesChannel->getId(),
                    'senderName' => $salesChannel->getName(),
                    'subject' => 'New document for your order',
                    'testMode' => false,
                ],
            );

        static::assertEquals(Response::HTTP_OK, $this->getBrowser()->getResponse()->getStatusCode());
    }

    private function createCustomer(Context $context): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'customerNumber' => '1337',
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'cicada',
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
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
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
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
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
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
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

        $orderRepository = static::getContainer()->get('order.repository');

        $orderRepository->upsert([$order], $context);

        return $orderId;
    }

    private function createDocumentWithFile(string $orderId, Context $context, string $documentType = InvoiceRenderer::TYPE): string
    {
        $documentGenerator = static::getContainer()->get(DocumentGenerator::class);

        $operation = new DocumentGenerateOperation($orderId, FileTypes::PDF, []);
        $document = $documentGenerator->generate($documentType, [$orderId => $operation], $context)->getSuccess()->first();

        static::assertNotNull($document);

        return $document->getId();
    }
}
