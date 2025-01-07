<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Cms\Type;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\CriteriaCollection;
use Cicada\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Cms\SalesChannel\Struct\ImageSliderItemStruct;
use Cicada\Core\Content\Cms\SalesChannel\Struct\ImageSliderStruct;
use Cicada\Core\Content\Media\Cms\AbstractDefaultMediaResolver;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Cicada\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

#[Package('discovery')]
class ImageSliderTypeDataResolver extends AbstractCmsElementResolver
{
    /**
     * @internal
     */
    public function __construct(private readonly AbstractDefaultMediaResolver $mediaResolver)
    {
    }

    public function getType(): string
    {
        return 'image-slider';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $sliderItemsConfig = $slot->getFieldConfig()->get('sliderItems');
        if ($sliderItemsConfig === null || $sliderItemsConfig->isMapped() || $sliderItemsConfig->isDefault()) {
            return null;
        }

        $sliderItems = $sliderItemsConfig->getArrayValue();
        $mediaIds = array_column($sliderItems, 'mediaId');

        $criteria = new Criteria($mediaIds);

        $criteriaCollection = new CriteriaCollection();
        $criteriaCollection->add('media_' . $slot->getUniqueIdentifier(), MediaDefinition::class, $criteria);

        return $criteriaCollection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $imageSlider = new ImageSliderStruct();
        $slot->setData($imageSlider);

        $navigation = $config->get('navigation');
        if ($navigation !== null && $navigation->isStatic()) {
            $imageSlider->setNavigation($navigation->getArrayValue());
        }

        $sliderItemsConfig = $config->get('sliderItems');
        if ($sliderItemsConfig === null) {
            return;
        }

        if ($sliderItemsConfig->isStatic()) {
            foreach ($sliderItemsConfig->getArrayValue() as $sliderItem) {
                $this->addMedia($slot, $imageSlider, $result, $sliderItem);
            }
        }

        if ($sliderItemsConfig->isDefault()) {
            foreach ($sliderItemsConfig->getArrayValue() as $sliderItem) {
                $this->addDefaultMediaToImageSlider($imageSlider, $sliderItem);
            }
        }

        if ($sliderItemsConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $sliderItems = $this->resolveEntityValue($resolverContext->getEntity(), $sliderItemsConfig->getStringValue());

            if ($sliderItems === null || (is_countable($sliderItems) ? \count($sliderItems) : 0) < 1) {
                return;
            }

            if ($sliderItemsConfig->getStringValue() === 'product.media') {
                /** @var ProductEntity $productEntity */
                $productEntity = $resolverContext->getEntity();

                if ($productEntity->getCover()) {
                    /** @var ProductMediaCollection $sliderItems */
                    $sliderItems = new ProductMediaCollection(array_merge(
                        [$productEntity->getCoverId() => $productEntity->getCover()],
                        $sliderItems->getElements()
                    ));
                }
            }

            foreach ($sliderItems->getMedia() as $media) {
                $imageSliderItem = new ImageSliderItemStruct();
                $imageSliderItem->setMedia($media);
                $imageSlider->addSliderItem($imageSliderItem);
            }
        }
    }

    /**
     * @deprecated tag:v6.7.0 - Will be removed without replacement
     */
    protected function sortItemsByPosition(ProductMediaCollection $sliderItems): void
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'Unused method ImageSliderTypeDataResolver::sortItemsByPosition will be removed without replacement');

        if (!$sliderItems->first() || !$sliderItems->first()->has('position')) {
            return;
        }

        $sliderItems->sort(static fn (ProductMediaEntity $a, ProductMediaEntity $b) => $a->get('position') - $b->get('position'));
    }

    /**
     * @param array{url?: string, newTab?: bool, mediaId: string} $config
     */
    private function addMedia(CmsSlotEntity $slot, ImageSliderStruct $imageSlider, ElementDataCollection $result, array $config): void
    {
        $imageSliderItem = new ImageSliderItemStruct();

        if (!empty($config['url'])) {
            $imageSliderItem->setUrl($config['url']);
            $imageSliderItem->setNewTab($config['newTab'] ?? false);
        }

        $searchResult = $result->get('media_' . $slot->getUniqueIdentifier());
        if (!$searchResult) {
            return;
        }

        /** @var MediaEntity|null $media */
        $media = $searchResult->get($config['mediaId']);
        if (!$media) {
            return;
        }

        $imageSliderItem->setMedia($media);
        $imageSlider->addSliderItem($imageSliderItem);
    }

    /**
     * @param array{fileName: string} $config
     */
    private function addDefaultMediaToImageSlider(ImageSliderStruct $imageSlider, array $config): void
    {
        $media = $this->mediaResolver->getDefaultCmsMediaEntity($config['fileName']);

        if ($media === null) {
            return;
        }

        $imageSliderItem = new ImageSliderItemStruct();
        $imageSliderItem->setMedia($media);
        $imageSlider->addSliderItem($imageSliderItem);
    }
}
