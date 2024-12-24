<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Flow\Dispatching\Storer\Stub;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\EventData\ScalarValueType;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Event\LanguageAware;

/**
 * @internal
 */
class LanguageAwareEvent implements FlowEventAware, LanguageAware
{
    public function __construct(private readonly ?string $languageId)
    {
    }

    public function getName(): string
    {
        return 'test';
    }

    public function getContext(): Context
    {
        return Context::createDefaultContext();
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('languageId', new ScalarValueType(ScalarValueType::TYPE_STRING));
    }

    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }
}
