<?php declare(strict_types=1);

namespace Cicada\Core\Installer\License;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Installer\Subscriber\InstallerLocaleListener;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('core')]
class LicenseFetcher
{
    /**
     * @param string[] $tosUrls
     */
    public function __construct(
        private readonly Client $guzzle,
        private readonly array $tosUrls
    ) {
    }

    public function fetch(Request $request): string
    {
        $locale = $request->attributes->get('_locale');
        $uri = $this->tosUrls[$locale] ?? $this->tosUrls[InstallerLocaleListener::FALLBACK_LOCALE];

        $response = $this->guzzle->get($uri);

        return $response->getBody()->getContents();
    }
}
