<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Checkout\Payload;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Checkout\Gateway\AppCheckoutGatewayResponse;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\Log\ExceptionLogger;
use Cicada\Core\Framework\Log\Package;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @internal only for use by the app-systems
 */
#[Package('core')]
class AppCheckoutGatewayPayloadService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly AppPayloadServiceHelper $helper,
        private readonly Client $client,
        private readonly ExceptionLogger $logger,
    ) {
    }

    public function request(string $url, AppCheckoutGatewayPayload $payload, AppEntity $app): ?AppCheckoutGatewayResponse
    {
        $optionRequest = $this->helper->createRequestOptions(
            $payload,
            $app,
            $payload->getSalesChannelContext()->getContext()
        );

        try {
            $response = $this->client->post($url, $optionRequest->jsonSerialize());
            $content = $response->getBody()->getContents();

            return new AppCheckoutGatewayResponse(\json_decode($content, true, flags: \JSON_THROW_ON_ERROR));
        } catch (GuzzleException $e) {
            $this->logger->logOrThrowException($e);

            return null;
        }
    }
}
