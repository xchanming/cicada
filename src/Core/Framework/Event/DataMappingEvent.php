<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('services-settings')]
class DataMappingEvent extends Event implements CicadaEvent
{
    /**
     * @param array<string, mixed> $output
     */
    public function __construct(
        private readonly DataBag $input,
        private array $output,
        private readonly Context $context
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getInput(): DataBag
    {
        return $this->input;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * @param array<string, mixed> $output
     */
    public function setOutput(array $output): void
    {
        $this->output = $output;
    }
}
