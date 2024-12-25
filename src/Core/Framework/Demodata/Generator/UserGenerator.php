<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Demodata\Generator;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Cicada\Core\Framework\Demodata\DemodataContext;
use Cicada\Core\Framework\Demodata\DemodataGeneratorInterface;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\System\User\UserDefinition;

/**
 * @internal
 */
#[Package('core')]
class UserGenerator implements DemodataGeneratorInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityWriterInterface $writer,
        private readonly UserDefinition $userDefinition,
        private readonly EntityRepository $languageRepository
    ) {
    }

    public function getDefinition(): string
    {
        return UserDefinition::class;
    }

    public function generate(int $numberOfItems, DemodataContext $context, array $options = []): void
    {
        $writeContext = WriteContext::createFromContext($context->getContext());

        $context->getConsole()->progressStart($numberOfItems);

        $payload = [];
        for ($i = 0; $i < $numberOfItems; ++$i) {
            $id = Uuid::randomHex();
            $name = $context->getFaker()->name();
            $title = $this->getRandomTitle();

            $user = [
                'id' => $id,
                'title' => $title,
                'name' => $name,
                'username' => $context->getFaker()->format('userName'),
                'email' => $id . $context->getFaker()->format('safeEmail'),
                'password' => 'cicada',
                'localeId' => $this->getLocaleId($context->getContext()),
            ];

            $payload[] = $user;

            if (\count($payload) >= 100) {
                $this->writer->upsert($this->userDefinition, $payload, $writeContext);

                $context->getConsole()->progressAdvance(\count($payload));

                $payload = [];
            }
        }

        if (!empty($payload)) {
            $this->writer->upsert($this->userDefinition, $payload, $writeContext);

            $context->getConsole()->progressAdvance(\count($payload));
        }

        $context->getConsole()->progressFinish();
    }

    private function getRandomTitle(): string
    {
        $titles = ['', 'Dr.', 'Dr. med.', 'Prof.', 'Prof. Dr.'];

        return $titles[array_rand($titles)];
    }

    private function getLocaleId(Context $context): string
    {
        /** @var LanguageEntity $first */
        $first = $this->languageRepository->search(new Criteria([Defaults::LANGUAGE_SYSTEM]), $context)->first();

        return $first->getLocaleId();
    }
}
