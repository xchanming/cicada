<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Listing\Processor;

use Cicada\Core\Content\Product\ProductException;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Cicada\Core\Content\Product\SalesChannel\Sorting\ProductSortingCollection;
use Cicada\Core\Content\Product\SalesChannel\Sorting\ProductSortingEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;

#[Package('inventory')]
class SortingListingProcessor extends AbstractListingProcessor
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $sortingRepository
    ) {
    }

    public function getDecorated(): AbstractListingProcessor
    {
        throw new DecorationPatternException(self::class);
    }

    public function prepare(Request $request, Criteria $criteria, SalesChannelContext $context): void
    {
        if (!$request->get('order')) {
            $request->request->set('order', $this->getSystemDefaultSortingKey($context));
        }

        /** @var ProductSortingCollection $sortings */
        $sortings = $criteria->getExtension('sortings') ?? new ProductSortingCollection();
        $sortings->merge($this->getAvailableSortings($request, $context->getContext()));

        $currentSorting = $this->getCurrentSorting($sortings, $request, $context->getSalesChannelId());

        if ($currentSorting !== null) {
            $criteria->addSorting(
                ...$currentSorting->createDalSorting()
            );
        }

        $criteria->addExtension('sortings', $sortings);
    }

    public function process(Request $request, ProductListingResult $result, SalesChannelContext $context): void
    {
        /** @var ProductSortingCollection $sortings */
        $sortings = $result->getCriteria()->getExtension('sortings');
        $currentSorting = $this->getCurrentSorting($sortings, $request, $context->getSalesChannelId());

        if ($currentSorting !== null) {
            $result->setSorting($currentSorting->getKey());
        }

        $result->setAvailableSortings($sortings);
    }

    private function getCurrentSorting(ProductSortingCollection $sortings, Request $request, string $salesChannelId): ?ProductSortingEntity
    {
        $key = $request->get('order');

        if (!\is_string($key)) {
            throw ProductException::sortingNotFoundException('');
        }

        $sorting = $sortings->getByKey($key);
        if ($sorting !== null) {
            return $sorting;
        }

        return $sortings->get($this->systemConfigService->getString('core.listing.defaultSorting', $salesChannelId));
    }

    private function getAvailableSortings(Request $request, Context $context): ProductSortingCollection
    {
        $criteria = new Criteria();
        $criteria->setTitle('product-listing::load-sortings');
        /** @var string[] $availableSortings */
        $availableSortings = $request->get('availableSortings');
        $availableSortingsById = [];
        $availableSortingsByName = [];

        if ($availableSortings) {
            arsort($availableSortings, \SORT_DESC | \SORT_NUMERIC);
            $availableSortingsFilter = array_keys($availableSortings);

            $availableSortingsById = array_filter($availableSortingsFilter, fn ($filter) => Uuid::isValid($filter));

            $filter = new EqualsAnyFilter('id', $availableSortingsById);

            $availableSortingsByName = array_filter($availableSortingsFilter, fn ($filter) => !Uuid::isValid($filter));
            if (!Feature::isActive('v6.7.0.0') && $availableSortingsByName) {
                Feature::triggerDeprecationOrThrow(
                    'v6.7.0.0',
                    'The sorting key in the product listing CMS element configuration has been replaced with the sorting ID. Please use the sorting ID instead.',
                );

                $filter = new OrFilter([
                    $filter,
                    new EqualsAnyFilter('key', $availableSortingsByName),
                ]);
            }

            $criteria->addFilter($filter);
        }

        $criteria
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('priority', 'DESC'));

        /** @var ProductSortingCollection $sortings */
        $sortings = $this->sortingRepository->search($criteria, $context)->getEntities();

        if ($availableSortingsById) {
            $sortings->sortByIdArray($availableSortingsById);
        }
        if ($availableSortingsByName && !Feature::isActive('v6.7.0.0')) {
            $sortings->sortByKeyArray($availableSortingsByName);
        }

        return $sortings;
    }

    private function getSystemDefaultSortingKey(SalesChannelContext $context): ?string
    {
        $id = $this->systemConfigService->getString(
            'core.listing.defaultSorting',
            $context->getSalesChannel()->getId()
        );

        if (empty($id)) {
            return null;
        }

        if (!Uuid::isValid($id)) {
            return $id;
        }

        $criteria = new Criteria([$id]);

        return $this->sortingRepository->search($criteria, $context->getContext())->first()?->get('key');
    }
}
