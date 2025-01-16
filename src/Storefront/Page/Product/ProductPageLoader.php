<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product;

use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Cicada\Core\Content\Product\Exception\ProductNotFoundException;
use Cicada\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class ProductPageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractProductDetailRoute $productDetailRoute
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     * @throws ProductNotFoundException
     */
    public function load(Request $request, SalesChannelContext $context): ProductPage
    {
        $productId = $request->attributes->get('productId');
        if (!$productId) {
            throw RoutingException::missingRequestParameter('productId', '/productId');
        }

        $criteria = (new Criteria())
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category')
            ->addAssociation('media.media');

        $criteria->getAssociation('media')->addSorting(
            new FieldSorting('position')
        );

        $this->eventDispatcher->dispatch(new ProductPageCriteriaEvent($productId, $criteria, $context));

        $result = $this->productDetailRoute->load($productId, $request, $context, $criteria);
        $product = $result->getProduct();

        if ($product->getMedia() && $product->getCover()) {
            $product->setMedia(new ProductMediaCollection(array_merge(
                [$product->getCover()->getId() => $product->getCover()],
                $product->getMedia()->getElements()
            )));
        }

        if ($category = $product->getSeoCategory()) {
            $request->request->set('navigationId', $category->getId());
        }

        $page = $this->genericLoader->load($request, $context);
        $page = ProductPage::createFrom($page);

        $page->setProduct($product);
        $page->setNavigationId($product->getId());

        if ($cmsPage = $product->getCmsPage()) {
            $page->setCmsPage($cmsPage);
        }
        $this->loadMetaData($page);

        $this->eventDispatcher->dispatch(
            new ProductPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }

    private function loadMetaData(ProductPage $page): void
    {
        $metaInformation = $page->getMetaInformation();

        if (!$metaInformation) {
            return;
        }

        $metaDescription = $page->getProduct()->getTranslation('metaDescription')
            ?? $page->getProduct()->getTranslation('description');
        $metaInformation->setMetaDescription((string) $metaDescription);

        $metaInformation->setMetaKeywords((string) $page->getProduct()->getTranslation('keywords'));

        if ((string) $page->getProduct()->getTranslation('metaTitle') !== '') {
            $metaInformation->setMetaTitle((string) $page->getProduct()->getTranslation('metaTitle'));

            return;
        }

        $metaTitleParts = [$page->getProduct()->getTranslation('name')];

        $metaTitleParts[] = $page->getProduct()->getProductNumber();

        $metaInformation->setMetaTitle(implode(' | ', $metaTitleParts));
    }
}
