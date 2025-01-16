<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\SalesChannel;

use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Content\Newsletter\Event\NewsletterUnsubscribeEvent;
use Cicada\Core\Content\Newsletter\NewsletterException;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('after-sales')]
class NewsletterUnsubscribeRoute extends AbstractNewsletterUnsubscribeRoute
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

    public function getDecorated(): AbstractNewsletterUnsubscribeRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/newsletter/unsubscribe', name: 'store-api.newsletter.unsubscribe', methods: ['POST'])]
    public function unsubscribe(RequestDataBag $dataBag, SalesChannelContext $context): NoContentResponse
    {
        $data = $dataBag->only('email');

        if (empty($data['email']) || !\is_string($data['email'])) {
            throw NewsletterException::missingEmailParameter();
        }

        $recipient = $this->getNewsletterRecipient($data['email'], $context);

        $data['id'] = $recipient->getId();
        $data['status'] = NewsletterSubscribeRoute::STATUS_OPT_OUT;

        $validator = $this->getOptOutValidation();
        $this->validator->validate($data, $validator);

        $this->newsletterRecipientRepository->update([$data], $context->getContext());

        $event = new NewsletterUnsubscribeEvent($context->getContext(), $recipient, $context->getSalesChannelId());
        $this->eventDispatcher->dispatch($event);

        return new NoContentResponse();
    }

    private function getNewsletterRecipient(string $email, SalesChannelContext $context): NewsletterRecipientEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('email', $email),
            new EqualsFilter('salesChannelId', $context->getSalesChannelId())
        );
        $criteria->addAssociation('salutation');
        $criteria->setLimit(1);

        $newsletterRecipient = $this->newsletterRecipientRepository->search(
            $criteria,
            $context->getContext()
        )->getEntities()->first();

        if (!$newsletterRecipient instanceof NewsletterRecipientEntity) {
            throw NewsletterException::recipientNotFound('email', $email);
        }

        return $newsletterRecipient;
    }

    private function getOptOutValidation(): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('newsletter_recipient.opt_out');
        $definition->add('email', new NotBlank(), new Email());

        return $definition;
    }
}
