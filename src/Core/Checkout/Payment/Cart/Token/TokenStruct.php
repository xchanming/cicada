<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\Token;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('checkout')]
class TokenStruct extends Struct
{
    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $id;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $token;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $paymentMethodId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $transactionId;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $finishUrl;

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $errorUrl;

    /**
     * @deprecated tag:v6.7.0 - will be of type Throwable|null and strictly typed
     *
     * @var \Exception|null
     */
    protected $exception;

    /**
     * @var int Unix Timestamp
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $expires;

    public function __construct(
        ?string $id = null,
        ?string $token = null,
        ?string $paymentMethodId = null,
        ?string $transactionId = null,
        ?string $finishUrl = null,
        ?int $expires = null,
        ?string $errorUrl = null
    ) {
        $this->id = $id;
        $this->token = $token;
        $this->paymentMethodId = $paymentMethodId;
        $this->transactionId = $transactionId;
        $this->finishUrl = $finishUrl;
        $this->expires = $expires ?? 1800;
        $this->errorUrl = $errorUrl;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getFinishUrl(): ?string
    {
        return $this->finishUrl;
    }

    public function getErrorUrl(): ?string
    {
        return $this->errorUrl;
    }

    public function setErrorUrl(?string $errorUrl): void
    {
        $this->errorUrl = $errorUrl;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function isExpired(): bool
    {
        return $this->expires < time();
    }

    /**
     * @deprecated tag:v6.7.0 - return type will be Throwable
     *
     * @phpstan-ignore-next-line nothing to throw for extended types
     */
    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    /**
     * @deprecated tag:v6.7.0 - param `exception` will be of type Throwable
     *
     * @phpstan-ignore-next-line nothing to throw for extended types
     */
    public function setException(?\Exception $exception): void
    {
        $this->exception = $exception;
    }

    public function getApiAlias(): string
    {
        return 'payment_token';
    }
}
