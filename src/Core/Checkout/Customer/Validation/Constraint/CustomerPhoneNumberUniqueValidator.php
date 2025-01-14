<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Validation\Constraint;

use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

#[Package('checkout')]
class CustomerPhoneNumberUniqueValidator extends ConstraintValidator
{
    /**
     * @internal
     */
    public function __construct(private readonly Connection $connection)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CustomerPhoneNumberUnique) {
            throw new UnexpectedTypeException($constraint, CustomerPhoneNumberUnique::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $query = $this->connection->createQueryBuilder();

        /** @var array{phone_number: string, guest: int, bound_sales_channel_id: string|null}[] $results */
        $results = $query
            ->select('phone_number', 'guest', 'LOWER(HEX(bound_sales_channel_id)) as bound_sales_channel_id')
            ->from('customer')
            ->where($query->expr()->eq('phone_number', $query->createPositionalParameter($value)))
            ->executeQuery()
            ->fetchAllAssociative();

        $results = \array_filter($results, static function (array $entry) use ($constraint) {
            // Filter out guest entries
            if ($entry['guest']) {
                return false;
            }

            if ($entry['bound_sales_channel_id'] === null) {
                return true;
            }

            if ($entry['bound_sales_channel_id'] !== $constraint->getSalesChannelContext()->getSalesChannelId()) {
                return false;
            }

            return true;
        });

        // If we don't have anything, skip
        if ($results === []) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ phoneNumber }}', $this->formatValue($value))
            ->setCode(CustomerPhoneNumberUnique::CUSTOMER_PHONE_NUMBER_NOT_UNIQUE)
            ->addViolation();
    }
}
