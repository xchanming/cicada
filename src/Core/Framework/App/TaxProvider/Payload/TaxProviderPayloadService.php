<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\TaxProvider\Payload;

use Cicada\Core\Checkout\Cart\TaxProvider\Struct\TaxProviderResult;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Payload\AppPayloadServiceHelper;
use Cicada\Core\Framework\App\TaxProvider\Response\TaxProviderResponse;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * @internal only for use by the app-system
 */
#[Package('checkout')]
class TaxProviderPayloadService
{
    public function __construct(
        private readonly AppPayloadServiceHelper $helper,
        private readonly Client $client,
    ) {
    }

    public function request(
        string $url,
        TaxProviderPayload $payload,
        AppEntity $app,
        Context $context
    ): ?TaxProviderResult {
        $optionRequest = $this->helper->createRequestOptions($payload, $app, $context);

        try {
            $response = $this->client->post($url, $optionRequest->jsonSerialize());
            $content = $response->getBody()->getContents();

            return TaxProviderResponse::create(\json_decode($content, true, 512, \JSON_THROW_ON_ERROR));
        } catch (GuzzleException) {
            return null;
        }
    }
}
