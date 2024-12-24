<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('buyers-experience')]
abstract class AbstractLandingPageRoute
{
    abstract public function getDecorated(): AbstractLandingPageRoute;

    abstract public function load(string $landingPageId, Request $request, SalesChannelContext $context): LandingPageRouteResponse;
}
