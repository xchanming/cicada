<?php declare(strict_types=1);

namespace Cicada\Core\Content\ContactForm\Validation;

use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\BuildValidationEvent;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('buyers-experience')]
class ContactFormValidationFactory implements DataValidationFactoryInterface
{
    /**
     * The regex to check if string contains an url
     */
    final public const DOMAIN_NAME_REGEX = '/((https?:\/))/';

    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function create(SalesChannelContext $context): DataValidationDefinition
    {
        return $this->createContactFormValidation('contact_form.create', $context);
    }

    public function update(SalesChannelContext $context): DataValidationDefinition
    {
        return $this->createContactFormValidation('contact_form.update', $context);
    }

    private function createContactFormValidation(string $validationName, SalesChannelContext $context): DataValidationDefinition
    {
        $definition = new DataValidationDefinition($validationName);

        $definition
            ->add('salutationId', new NotBlank(), new EntityExists(['entity' => 'salutation', 'context' => $context->getContext()]))
            ->add('email', new NotBlank(), new Email())
            ->add('subject', new NotBlank())
            ->add('comment', new NotBlank())
            ->add('name', new Regex(['pattern' => self::DOMAIN_NAME_REGEX, 'match' => false]));

        $required = $this->systemConfigService->get('core.basicInformation.nameFieldRequired', $context->getSalesChannel()->getId());
        if ($required) {
            $definition->set('name', new NotBlank(), new Regex([
                'pattern' => self::DOMAIN_NAME_REGEX,
                'match' => false,
            ]));
        }

        $required = $this->systemConfigService->get('core.basicInformation.phoneNumberFieldRequired', $context->getSalesChannel()->getId());
        if ($required) {
            $definition->add('phone', new NotBlank());
        }

        $validationEvent = new BuildValidationEvent($definition, new DataBag(), $context->getContext());
        $this->eventDispatcher->dispatch($validationEvent, $validationEvent->getName());

        return $definition;
    }
}
