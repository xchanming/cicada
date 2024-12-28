<?php declare(strict_types=1);

namespace Cicada\Core\Content\Newsletter\SalesChannel;

use Cicada\Core\Checkout\Customer\Service\EmailIdnConverter;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientDefinition;
use Cicada\Core\Content\Newsletter\Aggregate\NewsletterRecipient\NewsletterRecipientEntity;
use Cicada\Core\Content\Newsletter\Event\NewsletterConfirmEvent;
use Cicada\Core\Content\Newsletter\Event\NewsletterRegisterEvent;
use Cicada\Core\Content\Newsletter\Event\NewsletterSubscribeUrlEvent;
use Cicada\Core\Content\Newsletter\NewsletterException;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\RateLimiter\Exception\RateLimitExceededException;
use Cicada\Core\Framework\RateLimiter\RateLimiter;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\BuildValidationEvent;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Cicada\Core\System\SalesChannel\NoContentResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiCustomFieldMapper;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @phpstan-type SubscribeRequest array{email: string, storefrontUrl: string, option: string, name?: string,zipCode?: string, city?: string, street?: string, salutationId?: string}
 */
#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('buyers-experience')]
class NewsletterSubscribeRoute extends AbstractNewsletterSubscribeRoute
{
    final public const STATUS_NOT_SET = 'notSet';
    final public const STATUS_OPT_IN = 'optIn';
    final public const STATUS_OPT_OUT = 'optOut';
    final public const STATUS_DIRECT = 'direct';

    /**
     * The subscription is directly active and does not need a confirmation.
     */
    final public const OPTION_DIRECT = 'direct';

    /**
     * An email will be send to the provided email addrees containing a link to the /newsletter/confirm route.
     */
    final public const OPTION_SUBSCRIBE = 'subscribe';

    /**
     * The email address will be removed from the newsletter subscriptions.
     */
    final public const OPTION_UNSUBSCRIBE = 'unsubscribe';

    /**
     * Confirms the newsletter subscription for the provided email address.
     */
    final public const OPTION_CONFIRM_SUBSCRIBE = 'confirmSubscribe';

    /**
     * The regex to check if string contains an url
     */
    final public const DOMAIN_NAME_REGEX = '/((https?:\/))/';

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $newsletterRecipientRepository,
        private readonly DataValidator $validator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SystemConfigService $systemConfigService,
        private readonly RateLimiter $rateLimiter,
        private readonly RequestStack $requestStack,
        private readonly StoreApiCustomFieldMapper $customFieldMapper
    ) {
    }

    public function getDecorated(): AbstractNewsletterSubscribeRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/newsletter/subscribe', name: 'store-api.newsletter.subscribe', methods: ['POST'])]
    public function subscribe(RequestDataBag $dataBag, SalesChannelContext $context, bool $validateStorefrontUrl = true): NoContentResponse
    {
        $doubleOptInDomain = $this->systemConfigService->getString(
            'core.newsletter.doubleOptInDomain',
            $context->getSalesChannelId()
        );
        if ($doubleOptInDomain !== '') {
            $dataBag->set('storefrontUrl', $doubleOptInDomain);
            $validateStorefrontUrl = false;
        }

        EmailIdnConverter::encodeDataBag($dataBag);

        $validator = $this->getOptInValidator($dataBag, $context, $validateStorefrontUrl);

        $this->validator->validate($dataBag->all(), $validator);

        if (($request = $this->requestStack->getMainRequest()) !== null && $request->getClientIp() !== null) {
            try {
                $this->rateLimiter->ensureAccepted(RateLimiter::NEWSLETTER_FORM, $request->getClientIp());
            } catch (RateLimitExceededException $e) {
                throw NewsletterException::newsletterThrottled($e->getWaitTime());
            }
        }

        /** @var SubscribeRequest $data */
        $data = $dataBag->only(
            'email',
            'title',
            'name',
            'zipCode',
            'city',
            'street',
            'salutationId',
            'option',
            'storefrontUrl',
            'customFields'
        );

        $recipientId = $this->getNewsletterRecipientId($data['email'], $context);

        if (isset($recipientId)) {
            /** @var NewsletterRecipientEntity $recipient */
            $recipient = $this->newsletterRecipientRepository->search(new Criteria([$recipientId]), $context->getContext())->first();

            // If the user was previously subscribed but has unsubscribed now, the `getConfirmedAt()`
            // will still be set. So we need to check for the status as well.
            if ($recipient->getStatus() !== self::STATUS_OPT_OUT && $recipient->getConfirmedAt()) {
                return new NoContentResponse();
            }
        }

        $data = $this->completeData($data, $context);
        if ($dataBag->get('customFields') instanceof RequestDataBag) {
            $data['customFields'] = $this->customFieldMapper->map(
                NewsletterRecipientDefinition::ENTITY_NAME,
                $dataBag->get('customFields')
            );
        }

        $this->newsletterRecipientRepository->upsert([$data], $context->getContext());

        $recipient = $this->getNewsletterRecipient('email', $data['email'], $context);

        if (!$this->isNewsletterDoi($context)) {
            $event = new NewsletterConfirmEvent($context->getContext(), $recipient, $context->getSalesChannel()->getId());
            $this->eventDispatcher->dispatch($event);

            return new NoContentResponse();
        }

        $hashedEmail = Hasher::hash($data['email'], 'sha1');
        $url = $this->getSubscribeUrl($context, $hashedEmail, $data['hash'], $data, $recipient);

        $event = new NewsletterRegisterEvent($context->getContext(), $recipient, $url, $context->getSalesChannel()->getId());
        $this->eventDispatcher->dispatch($event);

        return new NoContentResponse();
    }

    private function isNewsletterDoi(SalesChannelContext $context): bool
    {
        if ($context->getCustomerId() === null) {
            return $this->systemConfigService->getBool('core.newsletter.doubleOptIn', $context->getSalesChannelId());
        }

        return $this->systemConfigService->getBool('core.newsletter.doubleOptInRegistered', $context->getSalesChannelId());
    }

    private function getOptInValidator(DataBag $dataBag, SalesChannelContext $context, bool $validateStorefrontUrl): DataValidationDefinition
    {
        $definition = new DataValidationDefinition('newsletter_recipient.create');
        $definition->add('email', new NotBlank(), new Email())
            ->add('option', new NotBlank(), new Choice(array_keys($this->getOptionSelection($context))));

        if (!empty($dataBag->get('name'))) {
            $definition->add('name', new NotBlank(), new Regex([
                'pattern' => self::DOMAIN_NAME_REGEX,
                'match' => false,
            ]));
        }

        if ($validateStorefrontUrl) {
            $definition
                ->add('storefrontUrl', new NotBlank(), new Choice(array_values($this->getDomainUrls($context))));
        }

        $validationEvent = new BuildValidationEvent($definition, $dataBag, $context->getContext());
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());

        return $definition;
    }

    /**
     * @param SubscribeRequest $data
     *
     * @return array{id: string, languageId: string, salesChannelId: string, status: string, hash: string, email: string, storefrontUrl: string, name?: string, zipCode?: string, city?: string, street?: string, salutationId?: string}
     */
    private function completeData(array $data, SalesChannelContext $context): array
    {
        $id = $this->getNewsletterRecipientId($data['email'], $context);

        $data['id'] = $id ?: Uuid::randomHex();
        $data['languageId'] = $context->getContext()->getLanguageId();
        $data['salesChannelId'] = $context->getSalesChannel()->getId();
        $data['status'] = $this->getOptionSelection($context)[$data['option']];
        $data['hash'] = Uuid::randomHex();

        return $data;
    }

    private function getNewsletterRecipientId(string $email, SalesChannelContext $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('email', $email),
                new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()),
            ]),
        );
        $criteria->setLimit(1);

        return $this->newsletterRecipientRepository
            ->searchIds($criteria, $context->getContext())
            ->firstId();
    }

    /**
     * @return array<string, string>
     */
    private function getOptionSelection(SalesChannelContext $context): array
    {
        return [
            self::OPTION_DIRECT => $this->isNewsletterDoi($context) ? self::STATUS_NOT_SET : self::STATUS_DIRECT,
            self::OPTION_SUBSCRIBE => $this->isNewsletterDoi($context) ? self::STATUS_NOT_SET : self::STATUS_DIRECT,
            self::OPTION_CONFIRM_SUBSCRIBE => self::STATUS_OPT_IN,
            self::OPTION_UNSUBSCRIBE => self::STATUS_OPT_OUT,
        ];
    }

    private function getNewsletterRecipient(string $identifier, string $value, SalesChannelContext $context): NewsletterRecipientEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter($identifier, $value));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $context->getSalesChannelId()));
        $criteria->addAssociation('salutation');
        $criteria->setLimit(1);

        /** @var NewsletterRecipientEntity|null $newsletterRecipient */
        $newsletterRecipient = $this->newsletterRecipientRepository->search($criteria, $context->getContext())->getEntities()->first();

        if (!$newsletterRecipient) {
            throw NewsletterException::recipientNotFound($identifier, $value);
        }

        return $newsletterRecipient;
    }

    /**
     * @return string[]
     */
    private function getDomainUrls(SalesChannelContext $context): array
    {
        $salesChannelDomainCollection = $context->getSalesChannel()->getDomains();
        if ($salesChannelDomainCollection === null) {
            return [];
        }

        return array_map(static fn (SalesChannelDomainEntity $domainEntity) => rtrim($domainEntity->getUrl(), '/'), $salesChannelDomainCollection->getElements());
    }

    /**
     * @param array{storefrontUrl: string} $data
     */
    private function getSubscribeUrl(
        SalesChannelContext $context,
        string $hashedEmail,
        string $hash,
        array $data,
        NewsletterRecipientEntity $recipient
    ): string {
        $urlTemplate = $this->systemConfigService->get(
            'core.newsletter.subscribeUrl',
            $context->getSalesChannelId()
        );
        if (!\is_string($urlTemplate)) {
            $urlTemplate = '/newsletter-subscribe?em=%%HASHEDEMAIL%%&hash=%%SUBSCRIBEHASH%%';
        }

        $urlEvent = new NewsletterSubscribeUrlEvent($context, $urlTemplate, $hashedEmail, $hash, $data, $recipient);
        $this->eventDispatcher->dispatch($urlEvent);

        return $data['storefrontUrl'] . str_replace(
            [
                '%%HASHEDEMAIL%%',
                '%%SUBSCRIBEHASH%%',
            ],
            [
                $hashedEmail,
                $hash,
            ],
            $urlEvent->getSubscribeUrl()
        );
    }
}
