<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Country;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedHook;

/**
 * Triggered when the CountryStateDataPagelet is loaded
 *
 * @hook-use-case data_loading
 *
 * @since 6.4.8.0
 *
 * @final
 */
#[Package('discovery')]
class CountryStateDataPageletLoadedHook extends PageLoadedHook
{
    use SalesChannelContextAwareTrait;

    final public const HOOK_NAME = 'country-state-data-pagelet-loaded';

    public function __construct(
        private readonly CountryStateDataPagelet $pagelet,
        SalesChannelContext $context
    ) {
        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
    }

    public function getName(): string
    {
        return self::HOOK_NAME;
    }

    public function getPage(): CountryStateDataPagelet
    {
        return $this->pagelet;
    }
}
