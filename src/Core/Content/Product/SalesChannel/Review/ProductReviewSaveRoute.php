<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Review;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Service\EmailIdnConverter;
use Cicada\Core\Content\Product\Exception\ReviewNotActiveExeption;
use Cicada\Core\Content\Product\ProductException;
use Cicada\Core\Content\Product\SalesChannel\Review\Event\ReviewFormEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityNotExists;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\Framework\Validation\Exception\ConstraintViolationException;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('after-sales')]
class ProductReviewSaveRoute extends AbstractProductReviewSaveRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $repository,
        private readonly DataValidator $validator,
        private readonly SystemConfigService $config,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDecorated(): AbstractProductReviewSaveRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/product/{productId}/review', name: 'store-api.product-review.save', defaults: ['_loginRequired' => true], methods: ['POST'])]
    public function save(string $productId, RequestDataBag $data, SalesChannelContext $context): NoContentResponse
    {
        EmailIdnConverter::encodeDataBag($data);

        $this->checkReviewsActive($context);

        $customer = $context->getCustomer();
        \assert($customer instanceof CustomerEntity);

        $languageId = $context->getContext()->getLanguageId();
        $salesChannelId = $context->getSalesChannelId();

        $customerId = $customer->getId();

        if (!$data->has('name')) {
            $data->set('name', $customer->getName());
        }

        if (!$data->has('email')) {
            $data->set('email', $customer->getEmail());
        }

        $data->set('customerId', $customerId);
        $data->set('productId', $productId);
        $this->validate($data, $context->getContext());

        $review = [
            'productId' => $productId,
            'customerId' => $customerId,
            'salesChannelId' => $salesChannelId,
            'languageId' => $languageId,
            'externalUser' => $data->get('name'),
            'externalEmail' => $data->get('email'),
            'title' => $data->get('title'),
            'content' => $data->get('content'),
            'points' => $data->get('points'),
            'status' => false,
        ];

        if ($data->get('id')) {
            $review['id'] = $data->get('id');
        }

        $this->repository->upsert([$review], $context->getContext());

        $mail = $review['externalEmail'];
        $mail = \is_string($mail) ? $mail : '';
        $event = new ReviewFormEvent(
            $context->getContext(),
            $context->getSalesChannelId(),
            new MailRecipientStruct([$mail => $review['externalUser']]),
            $data,
            $productId,
            $customerId
        );

        $this->eventDispatcher->dispatch(
            $event,
            ReviewFormEvent::EVENT_NAME
        );

        return new NoContentResponse();
    }

    private function validate(DataBag $data, Context $context): void
    {
        $definition = new DataValidationDefinition('product.create_rating');

        $definition->add('name', new NotBlank());
        $definition->add('title', new NotBlank(), new Length(['min' => 5]));
        $definition->add('content', new NotBlank(), new Length(['min' => 40]));

        $definition->add('points', new GreaterThanOrEqual(1), new LessThanOrEqual(5));

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $data->get('customerId')));

        if ($data->get('id')) {
            $criteria->addFilter(new EqualsFilter('id', $data->get('id')));

            $definition->add('id', new EntityExists([
                'entity' => 'product_review',
                'context' => $context,
                'criteria' => $criteria,
            ]));
        } else {
            $criteria->addFilter(new EqualsFilter('productId', $data->get('productId')));

            $definition->add('customerId', new EntityNotExists([
                'entity' => 'product_review',
                'context' => $context,
                'criteria' => $criteria,
                'primaryProperty' => 'customerId',
            ]));
        }

        $this->validator->validate($data->all(), $definition);

        $violations = $this->validator->getViolations($data->all(), $definition);

        if (!$violations->count()) {
            return;
        }

        throw new ConstraintViolationException($violations, $data->all());
    }

    /**
     * @throws ReviewNotActiveExeption
     */
    private function checkReviewsActive(SalesChannelContext $context): void
    {
        $showReview = $this->config->get('core.listing.showReview', $context->getSalesChannelId());

        if (!$showReview) {
            throw ProductException::reviewNotActive();
        }
    }
}
