<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Media\Validator;

use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Framework\Media\StorefrontMediaValidatorInterface;
use Cicada\Storefront\Framework\StorefrontFrameworkException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Package('buyers-experience')]
class StorefrontMediaDocumentValidator implements StorefrontMediaValidatorInterface
{
    use MimeTypeValidationTrait;

    public function getType(): string
    {
        return 'documents';
    }

    public function validate(UploadedFile $file): void
    {
        $valid = $this->checkMimeType($file, [
            'pdf' => ['application/pdf', 'application/x-pdf'],
        ]);

        if (!$valid) {
            throw StorefrontFrameworkException::fileTypeNotAllowed((string) $file->getMimeType(), $this->getType());
        }
    }
}
