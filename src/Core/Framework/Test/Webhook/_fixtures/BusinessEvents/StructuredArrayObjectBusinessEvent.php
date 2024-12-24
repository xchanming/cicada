<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Webhook\_fixtures\BusinessEvents;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\ObjectType;
use Cicada\Core\Framework\Event\EventData\ScalarValueType;
use Cicada\Core\Framework\Event\FlowEventAware;

/**
 * @internal
 */
class StructuredArrayObjectBusinessEvent implements FlowEventAware, BusinessEventEncoderTestInterface
{
    private array $inner = [
        'string' => 'string',
        'bool' => true,
        'int' => 3,
        'float' => 1.3,
    ];

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add(
                'inner',
                (new ObjectType())
                    ->add('string', new ScalarValueType(ScalarValueType::TYPE_STRING))
                    ->add('bool', new ScalarValueType(ScalarValueType::TYPE_BOOL))
                    ->add('int', new ScalarValueType(ScalarValueType::TYPE_INT))
                    ->add('float', new ScalarValueType(ScalarValueType::TYPE_FLOAT))
            );
    }

    public function getEncodeValues(string $cicadaVersion): array
    {
        return [
            'inner' => [
                'string' => 'string',
                'bool' => true,
                'int' => 3,
                'float' => 1.3,
            ],
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

    public function getInner(): array
    {
        return $this->inner;
    }
}
