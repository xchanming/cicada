<?php declare(strict_types=1);

namespace Cicada\Core\Service;

use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\Log\Package;
use GuzzleHttp\Client;

/**
 * @internal
 */
#[Package('core')]
class AuthenticatedServiceClient
{
    public function __construct(
        public readonly Client $client,
        private readonly ServiceRegistryEntry $entry,
        private readonly Source $source,
    ) {
    }

    public function syncLicense(string $licenseKey = '', string $licenseHost = ''): void
    {
        if ($this->entry->licenseSyncEndPoint === null) {
            return;
        }

        $payload = [
            'source' => $this->source->jsonSerialize(),
            'licenseKey' => $licenseKey,
            'licenseHost' => $licenseHost,
        ];

        try {
            $this->client->post($this->entry->licenseSyncEndPoint, ['json' => $payload]);
        } catch (\Throwable $exception) {
            throw ServiceException::requestTransportError($exception);
        }
    }
}
