<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Price\GrossPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\NetPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tax\TaxEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('checkout')]
class PriceActionController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $taxRepository,
        private readonly NetPriceCalculator $netCalculator,
        private readonly GrossPriceCalculator $grossCalculator
    ) {
    }

    #[Route(path: 'api/_action/calculate-price', name: 'api.action.calculate-price', methods: ['POST'])]
    public function calculate(Request $request, Context $context): JsonResponse
    {
        if (!$request->request->has('price')) {
            throw CartException::priceParameterIsMissing();
        }
        if (!$request->request->has('taxId')) {
            throw CartException::taxIdParameterIsMissing();
        }

        $taxId = (string) $request->request->get('taxId');
        $price = (float) $request->request->get('price');
        $quantity = $request->request->getInt('quantity', 1);
        $output = (string) $request->request->get('output', 'gross');
        $preCalculated = $request->request->getBoolean('calculated', true);

        $taxes = $this->taxRepository->search(new Criteria([$taxId]), $context);
        $tax = $taxes->get($taxId);
        if (!$tax instanceof TaxEntity) {
            throw CartException::taxRuleNotFound($taxId);
        }

        $data = $this->calculatePrice($price, $tax->getTaxRate(), $quantity, $output, $preCalculated);

        return new JsonResponse(
            ['data' => $data]
        );
    }

    #[Route(path: 'api/_action/calculate-prices', name: 'api.action.calculate-prices', methods: ['POST'])]
    public function calculatePrices(Request $request, Context $context): JsonResponse
    {
        if (!$request->request->has('taxId')) {
            throw CartException::taxIdParameterIsMissing();
        }

        $taxId = $request->request->getAlnum('taxId');
        $productPrices = $request->request->all('prices');

        if (empty($productPrices)) {
            throw CartException::pricesParameterIsMissing();
        }

        $tax = $this->taxRepository->search(new Criteria([$taxId]), $context)->get($taxId);
        if (!$tax instanceof TaxEntity) {
            throw CartException::taxRuleNotFound($taxId);
        }

        $data = [];
        $taxRate = $tax->getTaxRate();
        foreach ($productPrices as $productId => $prices) {
            $calculatedPrices = [];
            foreach ($prices as $price) {
                $quantity = $price['quantity'] ?? 1;
                $output = $price['output'] ?? 'gross';
                $preCalculated = $price['calculated'] ?? true;

                $calculatedPrices[$price['currencyId']] = $this->calculatePrice((float) $price['price'], $taxRate, (int) $quantity, $output, (bool) $preCalculated);
            }

            $data[$productId] = $calculatedPrices;
        }

        return new JsonResponse(
            ['data' => $data]
        );
    }

    /**
     * @return array<mixed>
     */
    private function calculatePrice(float $price, float $taxRate, int $quantity, string $output, bool $preCalculated): array
    {
        $calculator = $this->grossCalculator;
        if ($output === 'net') {
            $calculator = $this->netCalculator;
        }

        $taxRules = new TaxRuleCollection([new TaxRule($taxRate)]);

        $definition = new QuantityPriceDefinition($price, $taxRules, $quantity);
        $definition->setIsCalculated($preCalculated);

        $config = new CashRoundingConfig(50, 0.01, true);

        $calculated = $calculator->calculate($definition, $config);

        return json_decode((string) json_encode($calculated, \JSON_PRESERVE_ZERO_FRACTION), true, 512, \JSON_THROW_ON_ERROR);
    }
}
