<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Package('checkout')]
class HandlePaymentMethodRouteResponse extends StoreApiResponse
{
    /**
     * @var ArrayStruct<string, mixed>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(?RedirectResponse $response)
    {
        parent::__construct(
            new ArrayStruct(
                [
                    'redirectResponse' => $response,
                ]
            )
        );
    }

    public function getRedirectResponse(): ?RedirectResponse
    {
        return $this->object->get('redirectResponse');
    }

    public function getObject(): Struct
    {
        return new ArrayStruct([
            'redirectUrl' => $this->getRedirectResponse() ? $this->getRedirectResponse()->getTargetUrl() : null,
        ]);
    }
}
