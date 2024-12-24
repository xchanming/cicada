<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Hmac\Guzzle;

use Cicada\Core\Framework\App\AppLocaleProvider;
use Cicada\Core\Framework\App\Hmac\RequestSigner;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[Package('core')]
class AuthMiddleware
{
    final public const APP_REQUEST_TYPE = 'request_type';

    final public const APP_SECRET = 'app_secret';

    final public const VALIDATED_RESPONSE = 'validated_response';

    final public const APP_REQUEST_CONTEXT = 'app_request_context';

    final public const CICADA_CONTEXT_LANGUAGE = 'sw-context-language';

    final public const CICADA_USER_LANGUAGE = 'sw-user-language';

    /**
     * @internal
     */
    public function __construct(
        private readonly string $cicadaVersion,
        private readonly AppLocaleProvider $localeProvider
    ) {
    }

    /**
     * @param callable(RequestInterface, array<mixed>): mixed $handler
     */
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $this->getDefaultHeaderRequest($request, $options);

            if (!isset($options[self::APP_REQUEST_TYPE])) {
                return $handler($request, $options);
            }

            if (!\is_array($options[self::APP_REQUEST_TYPE])) {
                throw new InvalidArgumentException('request_type must be array');
            }

            $optionsRequestType = $options[self::APP_REQUEST_TYPE];

            if (!isset($optionsRequestType[self::APP_SECRET])) {
                throw new InvalidArgumentException('app_secret is required');
            }

            $secret = $optionsRequestType[self::APP_SECRET];

            $signature = new RequestSigner();

            $request = $signature->signRequest($request, $secret);

            $requiredAuthentic = !empty($optionsRequestType[AuthMiddleware::VALIDATED_RESPONSE]);

            if (!$requiredAuthentic) {
                return $handler($request, $options);
            }

            $successCallback = function (ResponseInterface $response) use ($secret, $signature, $request) {
                if ($response->getStatusCode() !== 401) {
                    if (!$signature->isResponseAuthentic($response, $secret)) {
                        throw new ServerException(
                            'Could not verify the authenticity of the response',
                            $request,
                            $response
                        );
                    }
                }

                return $response;
            };
            $promise = $handler($request, $options);
            \assert($promise instanceof PromiseInterface);

            return $promise->then($successCallback);
        };
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getDefaultHeaderRequest(RequestInterface $request, array $options): RequestInterface
    {
        if (isset($options[self::APP_REQUEST_CONTEXT])) {
            $context = $options[self::APP_REQUEST_CONTEXT];
            if (!($context instanceof Context)) {
                throw new InvalidArgumentException('app_request_context must be instance of Context');
            }
            $request = $this->getLanguageHeaderRequest($request, $context);
        }

        if ($request->hasHeader('sw-version')) {
            return clone $request;
        }

        return $request->withAddedHeader('sw-version', $this->cicadaVersion);
    }

    private function getLanguageHeaderRequest(RequestInterface $request, Context $context): RequestInterface
    {
        $request = $request->withAddedHeader(self::CICADA_CONTEXT_LANGUAGE, $context->getLanguageId());

        return $request->withAddedHeader(self::CICADA_USER_LANGUAGE, $this->localeProvider->getLocaleFromContext($context));
    }
}
