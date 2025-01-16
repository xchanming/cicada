<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\System\SystemConfig\SystemConfigService;

#[Package('framework')]
class SeedingThemePathBuilder extends AbstractThemePathBuilder
{
    private const SYSTEM_CONFIG_KEY = 'storefront.themeSeed';

    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public function assemblePath(string $salesChannelId, string $themeId): string
    {
        return $this->generateNewPath($salesChannelId, $themeId, $this->getSeed($salesChannelId));
    }

    public function generateNewPath(string $salesChannelId, string $themeId, string $seed): string
    {
        return Hasher::hash($themeId . $salesChannelId . $seed);
    }

    public function saveSeed(string $salesChannelId, string $themeId, string $seed): void
    {
        $this->systemConfigService->set(self::SYSTEM_CONFIG_KEY, $seed, $salesChannelId);
    }

    public function getDecorated(): AbstractThemePathBuilder
    {
        throw new DecorationPatternException(self::class);
    }

    private function getSeed(string $salesChannelId): string
    {
        /** @var string|null $seed */
        $seed = $this->systemConfigService->get(self::SYSTEM_CONFIG_KEY, $salesChannelId);

        if (!$seed) {
            return '';
        }

        return $seed;
    }
}
