<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\SalesChannel;

use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Content\Newsletter\Event\NewsletterConfirmEvent;
use Cicada\Core\Content\Newsletter\NewsletterException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('buyers-experience')]
class NewsletterConfirmRoute extends AbstractNewsletterConfirmRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $newsletterRecipientRepository,
        private readonly DataValidator $validator,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDecorated(): AbstractNewsletterConfirmRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/newsletter/confirm', name: 'store-api.newsletter.confirm', methods: ['POST'])]
    public function confirm(RequestDataBag $dataBag, SalesChannelContext $context): NoContentResponse
    {
        $recipient = $this->getNewsletterRecipient('hash', $dataBag->get('hash', ''), $context->getContext());

        $data = [
            'id' => $recipient->getId(),
            'status' => $recipient->getStatus(),
            'confirmedAt' => $recipient->getConfirmedAt(),
            'em' => $dataBag->get('em'),
        ];

        $this->validator->validate($data, $this->getBeforeConfirmSubscribeValidation(Hasher::hash($recipient->getEmail(), 'sha1')));

        $data['status'] = NewsletterSubscribeRoute::STATUS_OPT_IN;
        $data['confirmedAt'] = new \DateTime();

        $this->newsletterRecipientRepository->update([$data], $context->getContext());

        $event = new NewsletterConfirmEvent($context->getContext(), $recipient, $context->getSalesChannelId());
        $this->eventDispatcher->dispatch($event);

        return new NoContentResponse();
    }

    private function getNewsletterRecipient(string $identifier, string $value, Context $context): NewsletterRecipientEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter($identifier, $value));
        $criteria->addAssociation('salutation');
        $criteria->setLimit(1);

        /** @var NewsletterRecipientEntity|null $newsletterRecipient */
        $newsletterRecipient = $this->newsletterRecipientRepository->search($criteria, $context)->getEntities()->first();

        if (!$newsletterRecipient) {
            throw NewsletterException::recipientNotFound($identifier, $value);
        }

        return $newsletterRecipient;
    }

    private function getBeforeConfirmSubscribeValidation(string $emHash): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('newsletter_recipient.opt_in_before');
        $definition->add('id', new NotBlank())
            ->add('status', new EqualTo(['value' => NewsletterSubscribeRoute::STATUS_NOT_SET]))
            ->add('em', new EqualTo(['value' => $emHash]));

        return $definition;
    }
}
