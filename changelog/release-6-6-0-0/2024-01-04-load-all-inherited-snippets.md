---
title: Load all inherited snippets
issue: NEXT-24159
---

# Storefront
* Changed `Cicada\Core\System\Snippet\SnippetService` to load all inherited snippets even from level 2 and above inheritances.
* Changed argument `$salesChannelThemeLoader` to `DatabseSalesChannelThemeLoader` in `Cicada\Core\System\Snippet\SnippetService`
* Changed `Cicada\Storefront\Theme\Twig\ThemeNamespaceHierarchyBuilder` to use new `DatabaseSalsChannelThemeLoader`.
* Changed argument `$salesChannelThemeLoader` to `DatabaseSalesChannelThemeLoader` from `Cicada\Storefront\Theme\Twig\ThemeNamespaceHierarchyBuilder`
* Added new abstract class `Cicada\Storefront\Theme\AbstractSalesChannelThemeLoader`
* Added `Cicada\Storefront\Theme\DatabaseSalesChannelThemeLoader` as a cachable variant of `Cicada\Storefront\Theme\SalesChannelThemeLoader`
* Deprecated `\Cicada\Storefront\Theme\SalesChannelThemeLoader`, use `\Cicada\Storefront\Theme\DatabaseSalesChannelThemeLoader` instead.
