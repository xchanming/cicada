<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Validation\Constraint;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraint;

#[Package('checkout')]
class CustomerPhoneNumberUnique extends Constraint
{
    final public const CUSTOMER_PHONE_NUMBER_NOT_UNIQUE = 'f35d2b6e-d0c1-48c2-9b9e-86fdbff0d4ad';

    protected const ERROR_NAMES = [
        self::CUSTOMER_PHONE_NUMBER_NOT_UNIQUE => 'CUSTOMER_PHONE_NUMBER_NOT_UNIQUE',
    ];

    public string $message = 'The phone number {{ phoneNumber }} is already in use.';

    protected Context $context;

    protected SalesChannelContext $salesChannelContext;

    /**
     * @param array{context: Context, salesChannelContext: SalesChannelContext} $options
     *
     * @internal
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
