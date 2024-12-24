<?php declare(strict_types=1);

namespace Cicada\Core\System\Snippet\Exception;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Snippet\SnippetException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated tag:v6.7.0 - Will be removed in v6.7.0.0. Use SnippetException::invalidSnippetFile instead
 *
 * @codeCoverageIgnore
 */
#[Package('services-settings')]
class InvalidSnippetFileException extends SnippetException
{
    public function __construct(string $locale)
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.7.0.0', 'SnippetException::invalidSnippetFile'),
        );

        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            'FRAMEWORK__INVALID_SNIPPET_FILE',
            'The base snippet file for locale {{ locale }} is not registered.',
            ['locale' => $locale]
        );
    }
}
