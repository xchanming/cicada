<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\RecoverPassword;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedHook;

/**
 * Triggered when the AccountRecoverPasswordPage is loaded
 *
 * @hook-use-case data_loading
 *
 * @since 6.4.13.0
 *
 * @final
 */
#[Package('checkout')]
class AccountRecoverPasswordPageLoadedHook extends PageLoadedHook
{
    use SalesChannelContextAwareTrait;

    final public const HOOK_NAME = 'account-recover-password-page-loaded';

    public function __construct(
        private readonly AccountRecoverPasswordPage $page,
        SalesChannelContext $context
    ) {
        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
    }

    public function getName(): string
    {
        return self::HOOK_NAME;
    }

    public function getPage(): AccountRecoverPasswordPage
    {
        return $this->page;
    }
}
