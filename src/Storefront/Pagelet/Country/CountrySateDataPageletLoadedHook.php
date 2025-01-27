<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Country;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedHook;

/**
 * Deprecated class with the typo in the HOOK_NAME constant.
 *
 * @deprecated tag:v6.7.0 - Use CountryStateDataPageletLoadedHook instead
 *
 * @hook-use-case data_loading
 *
 * @since 6.7.0.0
 *
 * @final
 */
#[Package('discovery')]
class CountrySateDataPageletLoadedHook extends PageLoadedHook
{
    use SalesChannelContextAwareTrait;

    final public const HOOK_NAME = 'country-sate-data-pagelet-loaded';

    public function __construct(
        private readonly CountryStateDataPagelet $pagelet,
        SalesChannelContext $context
    ) {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Use CountryStateDataPageletLoadedHook instead of CountrySateDataPageletLoadedHook'
        );
        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
    }

    public function getName(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Use CountryStateDataPageletLoadedHook instead of CountrySateDataPageletLoadedHook'
        );

        return self::HOOK_NAME;
    }

    public function getPage(): CountryStateDataPagelet
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Use CountryStateDataPageletLoadedHook instead of CountrySateDataPageletLoadedHook'
        );

        return $this->pagelet;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            'Use CountryStateDataPageletLoadedHook instead of CountrySateDataPageletLoadedHook'
        );

        return $this->salesChannelContext;
    }
}
