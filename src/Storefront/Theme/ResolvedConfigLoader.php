<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('framework')]
class ResolvedConfigLoader extends AbstractResolvedConfigLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $repository,
        private readonly ThemeService $service
    ) {
    }

    public function getDecorated(): AbstractResolvedConfigLoader
    {
        throw new DecorationPatternException(self::class);
    }

    public function load(string $themeId, SalesChannelContext $context): array
    {
        $config = $this->service->getThemeConfiguration($themeId, false, $context->getContext());
        $resolvedConfig = [];
        $mediaItems = [];
        if (!\array_key_exists('fields', $config)) {
            return [];
        }

        foreach ($config['fields'] as $key => $data) {
            if ($data['type'] === 'media' && $data['value'] && Uuid::isValid($data['value'])) {
                $mediaItems[$data['value']][] = $key;
            }
            $resolvedConfig[$key] = $data['value'];
        }

        $result = new MediaCollection();

        /** @var array<string> $mediaIds */
        $mediaIds = array_keys($mediaItems);
        if (!empty($mediaIds)) {
            $criteria = new Criteria($mediaIds);
            $criteria->setTitle('theme-service::resolve-media');
            $result = $this->repository->search($criteria, $context->getContext());
        }

        foreach ($result as $media) {
            if (!\array_key_exists($media->getId(), $mediaItems)) {
                continue;
            }

            foreach ($mediaItems[$media->getId()] as $key) {
                $resolvedConfig[$key] = $media->getUrl();
            }
        }

        return $resolvedConfig;
    }
}
