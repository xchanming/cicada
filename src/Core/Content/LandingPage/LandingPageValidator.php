<?php declare(strict_types=1);

namespace Cicada\Core\Content\LandingPage;

use Cicada\Core\Content\LandingPage\Aggregate\LandingPageSalesChannel\LandingPageSalesChannelDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Cicada\Core\Framework\DataAbstractionLayer\Write\Validation\PostWriteValidationEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\WriteConstraintViolationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
#[Package('buyers-experience')]
class LandingPageValidator implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PostWriteValidationEvent::class => 'preValidate',
        ];
    }

    public function preValidate(PostWriteValidationEvent $event): void
    {
        $writeException = $event->getExceptions();
        $commands = $event->getCommands();
        $violationList = new ConstraintViolationList();

        foreach ($commands as $command) {
            if (!($command instanceof InsertCommand) || $command->getEntityName() !== LandingPageDefinition::ENTITY_NAME) {
                continue;
            }

            if (!$this->hasAnotherValidCommand($commands, $command)) {
                $violationList->addAll(
                    $this->validator->startContext()
                        ->atPath($command->getPath() . '/salesChannels')
                        ->validate(null, [new NotBlank()])
                        ->getViolations()
                );
                $writeException->add(new WriteConstraintViolationException($violationList));
            }
        }
    }

    /**
     * @param WriteCommand[] $commands
     */
    private function hasAnotherValidCommand(array $commands, WriteCommand $command): bool
    {
        $isValid = false;
        foreach ($commands as $searchCommand) {
            if ($searchCommand->getEntityName() === LandingPageSalesChannelDefinition::ENTITY_NAME && $searchCommand instanceof InsertCommand) {
                $searchPrimaryKey = $searchCommand->getPrimaryKey();
                $searchLandingPageId = $searchPrimaryKey['landing_page_id'] ?? null;

                $currentPrimaryKey = $command->getPrimaryKey();
                $currentLandingPageId = $currentPrimaryKey['id'] ?? null;

                if ($searchLandingPageId === $currentLandingPageId) {
                    $isValid = true;
                }
            }
        }

        return $isValid;
    }
}
