<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\DataAbstractionLayer;

use Cicada\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationCollection;
use Cicada\Core\Checkout\Payment\Aggregate\PaymentMethodTranslation\PaymentMethodTranslationEntity;
use Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentDistinguishableNameGenerator;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\App\Aggregate\AppPaymentMethod\AppPaymentMethodEntity;
use Cicada\Core\Framework\App\Aggregate\AppTranslation\AppTranslationCollection;
use Cicada\Core\Framework\App\Aggregate\AppTranslation\AppTranslationEntity;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Aggregate\PluginTranslation\PluginTranslationCollection;
use Cicada\Core\Framework\Plugin\Aggregate\PluginTranslation\PluginTranslationEntity;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentDistinguishableNameGenerator::class)]
class PaymentDistinguishableNameGeneratorTest extends TestCase
{
    public function testGeneratePlugin(): void
    {
        $pluginId = Uuid::randomHex();
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId($pluginId);
        $paymentMethod->setPluginId(Uuid::randomHex());
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());
        $paymentMethodTranslation = new PaymentMethodTranslationEntity();
        $paymentMethodTranslation->setId(Uuid::randomHex());
        $paymentMethodTranslation->setLanguageId(Uuid::randomHex());
        $paymentMethodTranslation->setName('TestPayment');
        $paymentMethod->getTranslations()?->add($paymentMethodTranslation);

        $plugin = new PluginEntity();
        $plugin->setId($pluginId);
        $plugin->setTranslations(new PluginTranslationCollection());
        $pluginTranslation = new PluginTranslationEntity();
        $pluginTranslation->setId(Uuid::randomHex());
        $pluginTranslation->setLanguageId($paymentMethodTranslation->getLanguageId());
        $pluginTranslation->setLabel('TestPlugin');
        $plugin->getTranslations()?->add($pluginTranslation);
        $paymentMethod->setPlugin($plugin);

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('plugin'));
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->getAssociation('plugin')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertSame([[[
            'id' => $paymentMethod->getId(),
            'distinguishableName' => [
                $paymentMethodTranslation->getLanguageId() => 'TestPayment | TestPlugin',
            ],
        ]]], $paymentRepository->upserts);
    }

    public function testGeneratePluginWithUnknownPluginTranslation(): void
    {
        $pluginId = Uuid::randomHex();
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setPluginId($pluginId);
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());
        $paymentMethodTranslation = new PaymentMethodTranslationEntity();
        $paymentMethodTranslation->setId(Uuid::randomHex());
        $paymentMethodTranslation->setLanguageId(Uuid::randomHex());
        $paymentMethodTranslation->setName('TestPayment');
        $paymentMethod->getTranslations()?->add($paymentMethodTranslation);

        $plugin = new PluginEntity();
        $plugin->setId($pluginId);
        $plugin->addTranslated('label', 'TestPlugin');
        $plugin->setTranslations(new PluginTranslationCollection());

        $paymentMethod->setPlugin($plugin);

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('plugin'));
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->getAssociation('plugin')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertSame([[[
            'id' => $paymentMethod->getId(),
            'distinguishableName' => [
                $paymentMethodTranslation->getLanguageId() => 'TestPayment | TestPlugin',
            ],
        ]]], $paymentRepository->upserts);
    }

    public function testGenerateWithoutTranslations(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('plugin'));
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->getAssociation('plugin')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertEmpty($paymentRepository->upserts);
    }

    public function testGenerateWithoutAppOrPlugin(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('plugin'));
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->getAssociation('plugin')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertEmpty($paymentRepository->upserts);
    }

    public function testGenerateWithoutPluginLoaded(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());
        $paymentMethodTranslation = new PaymentMethodTranslationEntity();
        $paymentMethodTranslation->setId(Uuid::randomHex());
        $paymentMethodTranslation->setLanguageId(Uuid::randomHex());
        $paymentMethodTranslation->setName('TestPayment');
        $paymentMethod->getTranslations()?->add($paymentMethodTranslation);
        $paymentMethod->setPluginId(Uuid::randomHex());

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('plugin'));
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->getAssociation('plugin')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertEmpty($paymentRepository->upserts);
    }

    public function testGenerateWithoutPluginTranslationsLoaded(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());
        $paymentMethodTranslation = new PaymentMethodTranslationEntity();
        $paymentMethodTranslation->setId(Uuid::randomHex());
        $paymentMethodTranslation->setLanguageId(Uuid::randomHex());
        $paymentMethodTranslation->setName('TestPayment');
        $paymentMethod->getTranslations()?->add($paymentMethodTranslation);
        $paymentMethod->setPluginId(Uuid::randomHex());
        $paymentMethod->setPlugin(new PluginEntity());

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('plugin'));
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->getAssociation('plugin')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertEmpty($paymentRepository->upserts);
    }

    public function testGenerateApp(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());
        $paymentMethodTranslation = new PaymentMethodTranslationEntity();
        $paymentMethodTranslation->setId(Uuid::randomHex());
        $paymentMethodTranslation->setLanguageId(Uuid::randomHex());
        $paymentMethodTranslation->setName('TestPayment');
        $paymentMethod->getTranslations()?->add($paymentMethodTranslation);

        $app = new AppEntity();
        $app->setTranslations(new AppTranslationCollection());
        $appTranslation = new AppTranslationEntity();
        $appTranslation->setId(Uuid::randomHex());
        $appTranslation->setLanguageId($paymentMethodTranslation->getLanguageId());
        $appTranslation->setLabel('TestApp');
        $app->getTranslations()?->add($appTranslation);
        $appPaymentMethod = new AppPaymentMethodEntity();
        $appPaymentMethod->setId(Uuid::randomHex());
        $appPaymentMethod->setApp($app);
        $paymentMethod->setAppPaymentMethod($appPaymentMethod);

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->hasAssociation('appPaymentMethod'));
                static::assertTrue($criteria->getAssociation('appPaymentMethod')->hasAssociation('app'));
                static::assertTrue($criteria->getAssociation('appPaymentMethod')->getAssociation('app')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertSame([[[
            'id' => $paymentMethod->getId(),
            'distinguishableName' => [
                $paymentMethodTranslation->getLanguageId() => 'TestPayment | TestApp',
            ],
        ]]], $paymentRepository->upserts);
    }

    public function testGenerateAppWithUnknownTranslation(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());
        $paymentMethodTranslation = new PaymentMethodTranslationEntity();
        $paymentMethodTranslation->setId(Uuid::randomHex());
        $paymentMethodTranslation->setLanguageId(Uuid::randomHex());
        $paymentMethodTranslation->setName('TestPayment');
        $paymentMethod->getTranslations()?->add($paymentMethodTranslation);

        $app = new AppEntity();
        $app->setTranslations(new AppTranslationCollection());
        $app->addTranslated('label', 'TestApp');
        $appPaymentMethod = new AppPaymentMethodEntity();
        $appPaymentMethod->setId(Uuid::randomHex());
        $appPaymentMethod->setApp($app);
        $paymentMethod->setAppPaymentMethod($appPaymentMethod);

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->hasAssociation('appPaymentMethod'));
                static::assertTrue($criteria->getAssociation('appPaymentMethod')->hasAssociation('app'));
                static::assertTrue($criteria->getAssociation('appPaymentMethod')->getAssociation('app')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertSame([[[
            'id' => $paymentMethod->getId(),
            'distinguishableName' => [
                $paymentMethodTranslation->getLanguageId() => 'TestPayment | TestApp',
            ],
        ]]], $paymentRepository->upserts);
    }

    public function testGenerateAppWithoutAppLoaded(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());
        $paymentMethodTranslation = new PaymentMethodTranslationEntity();
        $paymentMethodTranslation->setId(Uuid::randomHex());
        $paymentMethodTranslation->setLanguageId(Uuid::randomHex());
        $paymentMethodTranslation->setName('TestPayment');
        $paymentMethod->getTranslations()?->add($paymentMethodTranslation);

        $appPaymentMethod = new AppPaymentMethodEntity();
        $appPaymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setAppPaymentMethod($appPaymentMethod);

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->hasAssociation('appPaymentMethod'));
                static::assertTrue($criteria->getAssociation('appPaymentMethod')->hasAssociation('app'));
                static::assertTrue($criteria->getAssociation('appPaymentMethod')->getAssociation('app')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertEmpty($paymentRepository->upserts);
    }

    public function testGenerateAppWithoutAppTranslationLoaded(): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTranslations(new PaymentMethodTranslationCollection());
        $paymentMethodTranslation = new PaymentMethodTranslationEntity();
        $paymentMethodTranslation->setId(Uuid::randomHex());
        $paymentMethodTranslation->setLanguageId(Uuid::randomHex());
        $paymentMethodTranslation->setName('TestPayment');
        $paymentMethod->getTranslations()?->add($paymentMethodTranslation);

        $app = new AppEntity();
        $appPaymentMethod = new AppPaymentMethodEntity();
        $appPaymentMethod->setId(Uuid::randomHex());
        $appPaymentMethod->setApp($app);
        $paymentMethod->setAppPaymentMethod($appPaymentMethod);

        /** @var StaticEntityRepository<PaymentMethodCollection> $paymentRepository */
        $paymentRepository = new StaticEntityRepository([
            function (Criteria $criteria, Context $context) use ($paymentMethod): PaymentMethodCollection {
                static::assertTrue($criteria->hasAssociation('translations'));
                static::assertTrue($criteria->hasAssociation('appPaymentMethod'));
                static::assertTrue($criteria->getAssociation('appPaymentMethod')->hasAssociation('app'));
                static::assertTrue($criteria->getAssociation('appPaymentMethod')->getAssociation('app')->hasAssociation('translations'));
                static::assertSame(Context::SYSTEM_SCOPE, $context->getScope());

                return new PaymentMethodCollection([$paymentMethod]);
            },
        ]);

        $generator = new PaymentDistinguishableNameGenerator($paymentRepository);
        $generator->generateDistinguishablePaymentNames(Context::createDefaultContext());

        static::assertEmpty($paymentRepository->upserts);
    }
}
