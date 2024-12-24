<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\PlatformRequest;

#[Package('core')]
class ContextTokenResponse extends StoreApiResponse
{
    /**
     * @var ArrayStruct<string, mixed>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $object;

    public function __construct(
        string $token,
        ?string $redirectUrl = null
    ) {
        parent::__construct(new ArrayStruct([
            'redirectUrl' => $redirectUrl,
        ]));

        $this->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $token);
    }

    public function getToken(): string
    {
        return $this->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
    }

    public function getRedirectUrl(): ?string
    {
        return $this->object->get('redirectUrl');
    }
}
