<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Hmac;

use Cicada\Core\Framework\Log\Package;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[Package('core')]
class RequestSigner
{
    final public const CICADA_APP_SIGNATURE = 'cicada-app-signature';

    final public const CICADA_SHOP_SIGNATURE = 'cicada-shop-signature';

    public function signRequest(RequestInterface $request, string $secret): RequestInterface
    {
        if ($request->getMethod() !== 'POST') {
            return clone $request;
        }

        $body = $request->getBody()->getContents();

        $request->getBody()->rewind();

        if (!\strlen($body)) {
            return clone $request;
        }

        return $request->withAddedHeader(self::CICADA_SHOP_SIGNATURE, $this->signPayload($body, $secret));
    }

    public function isResponseAuthentic(ResponseInterface $response, string $secret): bool
    {
        if (!$response->hasHeader(self::CICADA_APP_SIGNATURE)) {
            return false;
        }

        $responseSignature = $response->getHeaderLine(self::CICADA_APP_SIGNATURE);
        $compareSignature = $this->signPayload($response->getBody()->getContents(), $secret);

        $response->getBody()->rewind();

        return hash_equals($compareSignature, $responseSignature);
    }

    public function signPayload(string $payload, string $secretKey, string $algorithm = 'sha256'): string
    {
        return hash_hmac($algorithm, $payload, $secretKey);
    }
}
