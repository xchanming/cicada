<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\FieldType;

use Cicada\Core\Framework\Log\Package;
use Cron\CronExpression;

#[Package('checkout')]
class CronInterval extends CronExpression
{
    public const EMPTY_EXPRESSION = '* * * * *';

    public function equals(CronInterval $other): bool
    {
        return $this->getExpression() === $other->getExpression();
    }

    public function isEmpty(): bool
    {
        return $this->getExpression() === self::EMPTY_EXPRESSION;
    }

    public static function createFromCronExpression(CronExpression $cronExpression): self
    {
        return new self($cronExpression->getExpression() ?? self::EMPTY_EXPRESSION);
    }
}
