<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\SalesChannel;

use Cicada\Core\Content\LandingPage\LandingPageEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('buyers-experience')]
class LandingPageRouteResponse extends StoreApiResponse
{
    /**
     * @var LandingPageEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(LandingPageEntity $landingPage)
    {
        parent::__construct($landingPage);
    }

    public function getLandingPage(): LandingPageEntity
    {
        return $this->object;
    }
}
