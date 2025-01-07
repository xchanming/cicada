<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category;

use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('discovery')]
class CategoryException extends HttpException
{
    public const SERVICE_CATEGORY_NOT_FOUND = 'CHECKOUT__SERVICE_CATEGORY_NOT_FOUND';

    public const FOOTER_CATEGORY_NOT_FOUND = 'CHECKOUT__FOOTER_CATEGORY_NOT_FOUND';
    public const AFTER_CATEGORY_NOT_FOUND = 'CONTENT__AFTER_CATEGORY_NOT_FOUND';

    public static function pageNotFound(string $pageId): CicadaHttpException
    {
        return new PageNotFoundException($pageId);
    }

    public static function categoryNotFound(string $id): CicadaHttpException
    {
        return new CategoryNotFoundException($id);
    }

    public static function serviceCategoryNotFoundForSalesChannel(string $salesChannelName): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::SERVICE_CATEGORY_NOT_FOUND,
            'Service category, for sales channel {{ salesChannelName }}, is not set',
            ['salesChannelName' => $salesChannelName]
        );
    }

    public static function footerCategoryNotFoundForSalesChannel(string $salesChannelName): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::FOOTER_CATEGORY_NOT_FOUND,
            'Footer category, for sales channel {{ salesChannelName }}, is not set',
            ['salesChannelName' => $salesChannelName]
        );
    }

    public static function afterCategoryNotFound(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::AFTER_CATEGORY_NOT_FOUND,
            'Category to insert after not found.',
        );
    }
}
