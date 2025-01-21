<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\StateMachineStateFieldSerializer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;

#[Package('framework')]
class StateMachineStateField extends FkField
{
    /**
     * @param array $allowedWriteScopes List of scopes, for which changing the status value is still allowed without
     *                                  using the StateMachine
     */
    public function __construct(
        string $storageName,
        string $propertyName,
        private readonly string $stateMachineName,
        private readonly array $allowedWriteScopes = [Context::SYSTEM_SCOPE]
    ) {
        parent::__construct($storageName, $propertyName, StateMachineStateDefinition::class);
    }

    public function getStateMachineName(): string
    {
        return $this->stateMachineName;
    }

    public function getAllowedWriteScopes(): array
    {
        return $this->allowedWriteScopes;
    }

    protected function getSerializerClass(): string
    {
        return StateMachineStateFieldSerializer::class;
    }
}
