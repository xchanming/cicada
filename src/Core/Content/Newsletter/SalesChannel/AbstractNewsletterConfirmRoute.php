<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route is used to confirm the newsletter registration
 * The required parameters are: "hash" (received from the mail) and "email"
 */
#[Package('buyers-experience')]
abstract class AbstractNewsletterConfirmRoute
{
    abstract public function getDecorated(): AbstractNewsletterConfirmRoute;

    abstract public function confirm(RequestDataBag $dataBag, SalesChannelContext $context): NoContentResponse;
}
