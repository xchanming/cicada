<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\ScalarValueType;
use Cicada\Core\Framework\Event\FlowEventAware;

/**
 * @internal
 */
class ScalarBusinessEvent implements FlowEventAware, BusinessEventEncoderTestInterface
{
    private string $string = 'string';

    private bool $bool = true;

    private int $int = 3;

    private float $float = 1.3;

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('string', new ScalarValueType(ScalarValueType::TYPE_STRING))
            ->add('bool', new ScalarValueType(ScalarValueType::TYPE_BOOL))
            ->add('int', new ScalarValueType(ScalarValueType::TYPE_INT))
            ->add('float', new ScalarValueType(ScalarValueType::TYPE_FLOAT));
    }

    public function getEncodeValues(string $cicadaVersion): array
    {
        return [
            'string' => 'string',
            'bool' => true,
            'int' => 3,
            'float' => 1.3,
        ];
    }

    public function getName(): string
    {
        return 'test';
    }

    public function getContext(): Context
    {
        return Context::createDefaultContext();
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function isBool(): bool
    {
        return $this->bool;
    }

    public function getInt(): int
    {
        return $this->int;
    }

    public function getFloat(): float
    {
        return $this->float;
    }
}
