<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\TaxProvider;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Exception\TaxProviderExceptions;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\TaxProvider\Struct\TaxProviderResult;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\TaxProvider\Payload\TaxProviderPayload;
use Cicada\Core\Framework\App\TaxProvider\Payload\TaxProviderPayloadService;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\TaxProvider\TaxProviderCollection;
use Cicada\Core\System\TaxProvider\TaxProviderEntity;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Package('checkout')]
class TaxProviderProcessor
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $taxProviderRepository,
        private readonly LoggerInterface $logger,
        private readonly TaxAdjustment $adjustment,
        private readonly TaxProviderRegistry $registry,
        private readonly TaxProviderPayloadService $payloadService
    ) {
    }

    public function process(Cart $cart, SalesChannelContext $context): void
    {
        if ($context->getTaxState() === CartPrice::TAX_STATE_FREE) {
            return;
        }

        $taxProviders = $this->getTaxProviders($context);

        if ($taxProviders->count() === 0) {
            return;
        }

        $exceptions = new TaxProviderExceptions();

        $result = $this->buildTaxes(
            $taxProviders,
            $cart,
            $context,
            $exceptions
        );

        if ($exceptions->hasExceptions()) {
            $this->logger->error($exceptions->getMessage(), ['error' => $exceptions]);

            throw $exceptions;
        }

        if (!$result) {
            return;
        }

        $this->adjustment->adjust($cart, $result, $context);
    }

    private function getTaxProviders(SalesChannelContext $context): TaxProviderCollection
    {
        $criteria = (new Criteria())
            ->addAssociations(['availabilityRule', 'app'])
            ->addFilter(
                new AndFilter([
                    new EqualsFilter('active', true),
                    new OrFilter([
                        new EqualsFilter('availabilityRuleId', null),
                        new EqualsAnyFilter('availabilityRuleId', $context->getRuleIds()),
                    ]),
                ])
            );

        /** @var TaxProviderCollection $providers */
        $providers = $this->taxProviderRepository->search($criteria, $context->getContext())->getEntities();

        // we can safely sort the providers in php, as we do not expect more than a couple of providers
        // otherwise we would need to sort them in the database with an index many fields to be performant
        $providers->sortByPriority();

        return $providers;
    }

    private function buildTaxes(
        TaxProviderCollection $providers,
        Cart $cart,
        SalesChannelContext $context,
        TaxProviderExceptions $exceptions,
    ): ?TaxProviderResult {
        /** @var TaxProviderEntity $providerEntity */
        foreach ($providers->getElements() as $providerEntity) {
            // app providers
            if ($providerEntity->getApp() && $providerEntity->getProcessUrl()) {
                return $this->handleAppRequest($providerEntity->getApp(), $providerEntity->getProcessUrl(), $cart, $context);
            }

            $provider = $this->registry->get($providerEntity->getIdentifier());

            if (!$provider) {
                $exceptions->add(
                    $providerEntity->getIdentifier(),
                    new NotFoundHttpException(\sprintf('No tax provider found for identifier %s', $providerEntity->getIdentifier()))
                );

                continue;
            }

            try {
                $taxProviderStruct = $provider->provide($cart, $context);
            } catch (\Throwable $e) {
                $exceptions->add($providerEntity->getIdentifier(), $e);

                continue;
            }

            // taxes given - no need to continue
            if ($taxProviderStruct->declaresTaxes()) {
                return $taxProviderStruct;
            }
        }

        return null;
    }

    private function handleAppRequest(
        AppEntity $app,
        string $processUrl,
        Cart $cart,
        SalesChannelContext $context
    ): ?TaxProviderResult {
        return $this->payloadService->request(
            $processUrl,
            new TaxProviderPayload($cart, $context),
            $app,
            $context->getContext()
        );
    }
}
