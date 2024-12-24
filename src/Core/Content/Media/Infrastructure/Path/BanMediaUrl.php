<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\Infrastructure\Path;

use Cicada\Core\Content\Media\Core\Application\AbstractMediaUrlGenerator;
use Cicada\Core\Content\Media\Core\Application\MediaReverseProxy;
use Cicada\Core\Content\Media\Core\Params\UrlParams;
use Cicada\Core\Content\Media\Core\Params\UrlParamsSource;
use Cicada\Core\Content\Media\Event\MediaPathChangedEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class BanMediaUrl
{
    /**
     * @internal
     */
    public function __construct(
        private readonly MediaReverseProxy $gateway,
        private readonly AbstractMediaUrlGenerator $generator
    ) {
    }

    public function changed(MediaPathChangedEvent $event): void
    {
        if (!$this->gateway->enabled()) {
            return;
        }

        $params = [];
        foreach ($event->changed as $changed) {
            if (isset($changed['thumbnailId'])) {
                $params[] = new UrlParams(
                    id: $changed['thumbnailId'],
                    source: UrlParamsSource::THUMBNAIL,
                    path: $changed['path']
                );

                continue;
            }

            $params[] = new UrlParams(
                id: $changed['mediaId'],
                source: UrlParamsSource::MEDIA,
                path: $changed['path']
            );
        }

        if (empty($params)) {
            return;
        }

        $urls = $this->generator->generate($params);

        if (empty($urls)) {
            return;
        }

        $this->gateway->ban($urls);
    }
}
