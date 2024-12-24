<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Payment\DataAbstractionLayer;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentDistinguishableNameSubscriber;
use Cicada\Core\Checkout\Payment\PaymentEvents;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentDistinguishableNameSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    private PaymentDistinguishableNameSubscriber $subscriber;

    private Context $context;

    protected function setUp(): void
    {
        $this->subscriber = new PaymentDistinguishableNameSubscriber();
        $this->context = Context::createDefaultContext();
    }

    public function testSubscribedEvents(): void
    {
        static::assertEquals(
            [
                PaymentEvents::PAYMENT_METHOD_LOADED_EVENT => 'addDistinguishablePaymentName',
            ],
            $this->subscriber->getSubscribedEvents()
        );
    }

    public function testFallsBackToPaymentMethodNameIfDistinguishableNameIsNotSet(): void
    {
        $paymentRepository = static::getContainer()->get('payment_method.repository');

        $paymentRepository->create(
            [
                [
                    'id' => $creditCardPaymentId = Uuid::randomHex(),
                    'name' => [
                        'en-GB' => 'Credit card',
                        'de-DE' => 'Kreditkarte',
                    ],
                    'technicalName' => 'payment_creditcard',
                    'active' => true,
                ],
                [
                    'id' => $invoicePaymentByCicadaPluginId = Uuid::randomHex(),
                    'name' => [
                        'en-GB' => 'Invoice',
                        'de-DE' => 'Rechnungskauf',
                    ],
                    'technicalName' => 'payment_invoice1',
                    'active' => true,
                    'plugin' => [
                        'name' => 'Cicada',
                        'baseClass' => 'Swag\Paypal',
                        'autoload' => [],
                        'version' => '1.0.0',
                        'label' => [
                            'en-GB' => 'Cicada (English)',
                            'de-DE' => 'Cicada (Deutsch)',
                        ],
                    ],
                ],
                [
                    'id' => $invoicePaymentByPluginId = Uuid::randomHex(),
                    'name' => [
                        'en-GB' => 'Invoice',
                        'de-DE' => 'Rechnung',
                    ],
                    'technicalName' => 'payment_invoice2',
                    'active' => true,
                    'plugin' => [
                        'name' => 'Plugin',
                        'baseClass' => 'Plugin\Paypal',
                        'autoload' => [],
                        'version' => '1.0.0',
                        'label' => [
                            'en-GB' => 'Plugin (English)',
                            'de-DE' => 'Plugin (Deutsch)',
                        ],
                    ],
                ],
                [
                    'id' => $invoicePaymentByAppId = Uuid::randomHex(),
                    'name' => [
                        'en-GB' => 'Invoice',
                        'de-DE' => 'Rechnung',
                    ],
                    'technicalName' => 'payment_invoice3',
                    'active' => true,
                    'appPaymentMethod' => [
                        'identifier' => 'identifier',
                        'appName' => 'appName',
                        'app' => [
                            'name' => 'App',
                            'path' => 'path',
                            'version' => '1.0.0',
                            'label' => 'App',
                            'integration' => [
                                'accessKey' => 'accessKey',
                                'secretAccessKey' => 'secretAccessKey',
                                'label' => 'Integration',
                            ],
                            'aclRole' => [
                                'name' => 'aclRole',
                            ],
                        ],
                    ],
                ],
            ],
            $this->context
        );

        /** @var PaymentMethodCollection $payments */
        $payments = $paymentRepository
            ->search(new Criteria(), $this->context)
            ->getEntities();

        $creditCardPayment = $payments->get($creditCardPaymentId);
        static::assertNotNull($creditCardPayment);
        static::assertEquals('Credit card', $creditCardPayment->getDistinguishableName());

        /** @var PaymentMethodEntity $invoicePaymentByCicadaPlugin */
        $invoicePaymentByCicadaPlugin = $payments->get($invoicePaymentByCicadaPluginId);
        static::assertEquals('Invoice | Cicada (English)', $invoicePaymentByCicadaPlugin->getDistinguishableName());

        /** @var PaymentMethodEntity $invoicePaymentByPlugin */
        $invoicePaymentByPlugin = $payments->get($invoicePaymentByPluginId);
        static::assertEquals('Invoice | Plugin (English)', $invoicePaymentByPlugin->getDistinguishableName());

        /** @var PaymentMethodEntity $invoicePaymentByApp */
        $invoicePaymentByApp = $payments->get($invoicePaymentByAppId);
        static::assertEquals('Invoice | App', $invoicePaymentByApp->getDistinguishableName());

        /** @var PaymentMethodEntity $paidInAdvance */
        $paidInAdvance = $payments
            ->filterByProperty('name', 'Paid in advance')
            ->first();

        static::assertEquals($paidInAdvance->getTranslation('name'), $paidInAdvance->getTranslation('distinguishableName'));

        $germanContext = new Context(
            new SystemSource(),
            [],
            Defaults::CURRENCY,
            [$this->getDeDeLanguageId(), Defaults::LANGUAGE_SYSTEM]
        );

        /** @var PaymentMethodCollection $payments */
        $payments = $paymentRepository
            ->search(new Criteria(), $germanContext)
            ->getEntities();

        $creditCardPayment = $payments->get($creditCardPaymentId);
        static::assertNotNull($creditCardPayment);
        static::assertEquals('Kreditkarte', $creditCardPayment->getDistinguishableName());

        /** @var PaymentMethodEntity $invoicePaymentByCicadaPlugin */
        $invoicePaymentByCicadaPlugin = $payments->get($invoicePaymentByCicadaPluginId);
        static::assertEquals('Rechnungskauf | Cicada (Deutsch)', $invoicePaymentByCicadaPlugin->getDistinguishableName());

        /** @var PaymentMethodEntity $invoicePaymentByPlugin */
        $invoicePaymentByPlugin = $payments->get($invoicePaymentByPluginId);
        static::assertEquals('Rechnung | Plugin (Deutsch)', $invoicePaymentByPlugin->getDistinguishableName());

        /** @var PaymentMethodEntity $invoicePaymentByApp */
        $invoicePaymentByApp = $payments->get($invoicePaymentByAppId);
        static::assertEquals('Rechnung | App', $invoicePaymentByApp->getDistinguishableName());
    }
}
