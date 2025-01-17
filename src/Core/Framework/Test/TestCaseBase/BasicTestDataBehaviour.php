<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\TestCaseBase;

use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait BasicTestDataBehaviour
{
    public function getZhCnLanguageId(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('language.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('language.translationCode.code', 'zh-CN'));

        /** @var string $languageId */
        $languageId = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $languageId;
    }

    abstract protected static function getContainer(): ContainerInterface;

    protected function getValidPaymentMethodId(?string $salesChannelId = null): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('payment_method.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true));

        if ($salesChannelId) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getInactivePaymentMethodId(?string $salesChannelId = null): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('payment_method.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', false));

        if ($salesChannelId) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getAvailablePaymentMethod(?string $salesChannelId = null): PaymentMethodEntity
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('payment_method.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('availabilityRuleId', null));

        if ($salesChannelId) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        /** @var PaymentMethodEntity|null $paymentMethod */
        $paymentMethod = $repository->search($criteria, Context::createDefaultContext())->getEntities()->first();

        if ($paymentMethod === null) {
            throw new \LogicException('No available Payment method configured');
        }

        return $paymentMethod;
    }

    protected function getValidShippingMethodId(?string $salesChannelId = null): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('shipping_method.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('name'));

        if ($salesChannelId) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getAvailableShippingMethod(?string $salesChannelId = null): ShippingMethodEntity
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('shipping_method.repository');

        $criteria = (new Criteria())
            ->addAssociation('prices')
            ->addFilter(new EqualsFilter('shipping_method.prices.calculation', 1))
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('name'));

        if ($salesChannelId) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        $shippingMethods = $repository->search($criteria, Context::createDefaultContext())->getEntities();

        /** @var ShippingMethodEntity $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->getAvailabilityRuleId() !== null) {
                return $shippingMethod;
            }
        }

        throw new \LogicException('No available ShippingMethod configured');
    }

    protected function getValidSalutationId(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('salutation.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addSorting(new FieldSorting('salutationKey'));

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getLocaleIdOfSystemLanguage(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('language.repository');

        /** @var LanguageEntity $language */
        $language = $repository->search(new Criteria([Defaults::LANGUAGE_SYSTEM]), Context::createDefaultContext())->get(Defaults::LANGUAGE_SYSTEM);

        return $language->getLocaleId();
    }

    protected function getSnippetSetIdForLocale(string $locale): ?string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('snippet_set.repository');

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('iso', $locale))
            ->setLimit(1);

        return $repository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }

    /**
     * @param string|null $salesChannelId (null when no saleschannel filtering)
     */
    protected function getValidCountryId(?string $salesChannelId = TestDefaults::SALES_CHANNEL): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('country.repository');

        $criteria = (new Criteria())->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('shippingAvailable', true))
            ->addSorting(new FieldSorting('iso'));

        if ($salesChannelId !== null) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    /**
     * @param string|null $salesChannelId (null when no saleschannel filtering)
     */
    protected function getValidCountryCityId(?string $salesChannelId = TestDefaults::SALES_CHANNEL): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('country_state.repository');

        $criteria = (new Criteria())->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('countryId', $this->getValidCountryId($salesChannelId)))
            ->addFilter(new EqualsFilter('shortCode', '5101'));

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getCnCountryId(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('country.repository');

        $criteria = (new Criteria())->setLimit(1)
            ->addFilter(new EqualsFilter('iso', 'CN'));

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getValidCategoryId(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('category.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addSorting(new FieldSorting('level'), new FieldSorting('name'));

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getValidTaxId(): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('tax.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addSorting(new FieldSorting('name'));

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getStateMachineState(string $stateMachine = OrderStates::STATE_MACHINE, string $state = OrderStates::STATE_OPEN): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('state_machine_state.repository');

        $criteria = new Criteria();
        $criteria
            ->setLimit(1)
            ->addFilter(new EqualsFilter('technicalName', $state))
            ->addFilter(new EqualsFilter('stateMachine.technicalName', $stateMachine));

        /** @var string $id */
        $id = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();

        return $id;
    }

    protected function getCurrencyIdByIso(string $iso = 'CNY'): string
    {
        $connection = static::getContainer()->get(Connection::class);

        return Uuid::fromBytesToHex($connection->fetchOne('SELECT id FROM currency WHERE iso_code = :iso', ['iso' => $iso]));
    }
}
