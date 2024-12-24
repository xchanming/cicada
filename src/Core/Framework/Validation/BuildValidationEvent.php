<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Validation;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\GenericEvent;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class BuildValidationEvent extends Event implements CicadaEvent, GenericEvent
{
    public function __construct(
        private readonly DataValidationDefinition $definition,
        private readonly DataBag $data,
        private readonly Context $context
    ) {
    }

    public function getName(): string
    {
        return 'framework.validation.' . $this->definition->getName();
    }

    public function getDefinition(): DataValidationDefinition
    {
        return $this->definition;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getData(): DataBag
    {
        return $this->data;
    }
}
