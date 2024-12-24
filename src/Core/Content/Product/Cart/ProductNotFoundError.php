<?php
declare(strict_types=1);

namespace Cicada\Core\Content\Product\Cart;

use Cicada\Core\Checkout\Cart\Error\Error;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductNotFoundError extends Error
{
    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $id;

    public function __construct(string $id)
    {
        $this->id = $id;

        parent::__construct('The product %s could not be found');
    }

    public function getParameters(): array
    {
        return ['id' => $this->id];
    }

    public function getId(): string
    {
        return $this->getMessageKey() . $this->id;
    }

    public function getMessageKey(): string
    {
        return 'product-not-found';
    }

    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }
}
