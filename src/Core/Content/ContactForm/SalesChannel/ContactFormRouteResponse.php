<?php declare(strict_types=1);

namespace Cicada\Core\Content\ContactForm\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('buyers-experience')]
class ContactFormRouteResponse extends StoreApiResponse
{
    /**
     * @var ContactFormRouteResponseStruct
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(ContactFormRouteResponseStruct $object)
    {
        parent::__construct($object);
    }

    public function getResult(): ContactFormRouteResponseStruct
    {
        return $this->object;
    }
}
