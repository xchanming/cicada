<?php declare(strict_types=1);

namespace Cicada\Storefront\Theme\Message;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\AsyncMessageInterface;

/**
 * @internal
 *
 * used to compile the themes in the queue
 */
#[Package('storefront')]
class CompileThemeMessage implements AsyncMessageInterface
{
    public function __construct(
        private readonly string $salesChannelId,
        private readonly string $themeId,
        private readonly bool $withAssets,
        private readonly Context $context
    ) {
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getThemeId(): string
    {
        return $this->themeId;
    }

    public function isWithAssets(): bool
    {
        return $this->withAssets;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
