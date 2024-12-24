<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ExtendableTrait;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class DocumentTemplateRendererParameterEvent extends Event
{
    use ExtendableTrait;

    public function __construct(private readonly array $parameters)
    {
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
