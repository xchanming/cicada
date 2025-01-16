<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route is used to unsubscribe the newsletter
 * The required parameters is "email"
 */
#[Package('after-sales')]
abstract class AbstractNewsletterUnsubscribeRoute
{
    abstract public function getDecorated(): AbstractNewsletterUnsubscribeRoute;

    abstract public function unsubscribe(RequestDataBag $dataBag, SalesChannelContext $context): NoContentResponse;
}
