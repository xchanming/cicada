---
title: Changed Store-API Response from Collection to Search Result
issue: NEXT-10272
---
# Core

*  Changed the constructor of following classes to `\Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult`:
    * `\Cicada\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse`
    * `\Cicada\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute`
    * `\Cicada\Core\Content\Seo\SalesChannel\SeoUrlRouteResponse`
    * `\Cicada\Core\System\Language\SalesChannel\LanguageRouteResponse`
    * `\Cicada\Core\System\Salutation\SalesChannel\SalutationRouteResponse`
    * `\Cicada\Core\System\Currency\SalesChannel\CurrencyRouteResponse`
___
# API

*  Changed the response from following routes to return a search result instead a collection
    * /store-api/v{version}/payment-method
    * /store-api/v{version}/shipping-method
    * /store-api/v{version}/seo-url
    * /store-api/v{version}/language
    * /store-api/v{version}/currency

## Before

```
POST /store-api/v3/currency

{
    "includes": {
        "currency": [
            "id",
            "factor",
            "shortName",
            "name"
        ]
    }
}

[
    {
        "factor": 0.89157,
        "shortName": "GBP",
        "name": "Pound",
        "id": "01913e4cbe604f45be84cbabd5966239",
        "apiAlias": "currency"
    },
    {
        "factor": 10.51,
        "shortName": "SEK",
        "name": "Swedish krone",
        "id": "3dfbaa78994b4f1cac491f1a992646fd",
        "apiAlias": "currency"
    }
]
```

## After

```
POST /store-api/v3/currency

{
    "includes": {
        "currency": [
            "id",
            "factor",
            "shortName",
            "name"
        ]
    }
}

[
    "total": 2,
    "aggregations": [],
    "elements": [
        {
            "factor": 0.89157,
            "shortName": "GBP",
            "name": "Pound",
            "id": "01913e4cbe604f45be84cbabd5966239",
            "apiAlias": "currency"
        },
        {
            "factor": 10.51,
            "shortName": "SEK",
            "name": "Swedish krone",
            "id": "3dfbaa78994b4f1cac491f1a992646fd",
            "apiAlias": "currency"
        }
    ]
]
```
