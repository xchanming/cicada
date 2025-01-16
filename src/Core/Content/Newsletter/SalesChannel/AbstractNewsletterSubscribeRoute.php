<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route is used to subscribe to the newsletter
 * The required parameters are: "email" and "option"
 * Valid "option" arguments: "subscribe" for double optin and "direct" to skip double optin
 * Optional parameters are: "salutationId", "name",  "street", "city" and "zipCode"
 */
#[Package('after-sales')]
abstract class AbstractNewsletterSubscribeRoute
{
    abstract public function getDecorated(): AbstractNewsletterSubscribeRoute;

    abstract public function subscribe(RequestDataBag $dataBag, SalesChannelContext $context, bool $validateStorefrontUrl): NoContentResponse;
}
