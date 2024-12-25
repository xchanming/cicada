<?php declare(strict_types=1);

namespace Cicada\Core\Content\ContactForm\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * This route can be used to send a contact form mail for the authenticated sales channel.
 * Required fields are: "salutationId", "name", "email", "phone", "subject" and "comment"
 */
#[Package('buyers-experience')]
abstract class AbstractContactFormRoute
{
    abstract public function getDecorated(): AbstractContactFormRoute;

    abstract public function load(RequestDataBag $data, SalesChannelContext $context): ContactFormRouteResponse;
}
