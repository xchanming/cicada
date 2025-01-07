<?php declare(strict_types=1);

namespace Cicada\Core\Content\Media\File;

use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use League\Flysystem\FilesystemOperator;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

#[Package('discovery')]
class FileLoader
{
    private readonly FileNameValidator $fileNameValidator;

    /**
     * @internal
     *
     * @param EntityRepository<MediaCollection> $mediaRepository
     */
    public function __construct(
        private readonly FilesystemOperator $filesystemPublic,
        private readonly FilesystemOperator $filesystemPrivate,
        private readonly EntityRepository $mediaRepository,
        private readonly StreamFactoryInterface $streamFactory
    ) {
        $this->fileNameValidator = new FileNameValidator();
    }

    public function loadMediaFile(string $mediaId, Context $context): string
    {
        $media = $this->findMediaById($mediaId, $context);

        return $this->getFileSystem($media)->read($this->getFilePath($media));
    }

    public function loadMediaFileStream(string $mediaId, Context $context): StreamInterface
    {
        $media = $this->findMediaById($mediaId, $context);
        $resource = $this->getFileSystem($media)->readStream($this->getFilePath($media));

        return $this->streamFactory->createStreamFromResource($resource);
    }

    private function getFilePath(MediaEntity $media): string
    {
        $this->fileNameValidator->validateFileName($media->getFileName() ?: '');

        return $media->getPath();
    }

    private function getFileSystem(MediaEntity $media): FilesystemOperator
    {
        if ($media->isPrivate()) {
            return $this->filesystemPrivate;
        }

        return $this->filesystemPublic;
    }

    /**
     * @throws MediaException
     */
    private function findMediaById(string $mediaId, Context $context): MediaEntity
    {
        $media = $this->mediaRepository->search(
            new Criteria([$mediaId]),
            $context,
        )->getEntities()->first();

        if ($media === null) {
            throw MediaException::mediaNotFound($mediaId);
        }

        return $media;
    }
}
