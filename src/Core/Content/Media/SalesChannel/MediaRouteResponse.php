<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\SalesChannel;

use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('core')]
class MediaRouteResponse extends StoreApiResponse
{
    /**
     * @var MediaCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(MediaCollection $mediaCollection)
    {
        parent::__construct($mediaCollection);
    }

    public function getMediaCollection(): MediaCollection
    {
        return $this->object;
    }
}
