<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Cart\Error;

use Cicada\Core\Checkout\Cart\Error\Error;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class PromotionExcludedError extends Error
{
    private const KEY = 'promotion-excluded';

    public function __construct(protected string $name)
    {
        $this->message = \sprintf('Promotion %s was excluded for cart.', $this->name);

        parent::__construct($this->message);
    }

    public function isPersistent(): bool
    {
        return false;
    }

    public function getId(): string
    {
        return self::KEY;
    }

    public function getLevel(): int
    {
        return self::LEVEL_NOTICE;
    }

    public function getMessageKey(): string
    {
        return self::KEY;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function blockOrder(): bool
    {
        return false;
    }

    public function getParameters(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
