<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1617000878AddTemplateDataToMailTemplateType extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1617000878;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            '
        ALTER TABLE `mail_template_type`
        ADD `template_data` LONGTEXT COLLATE utf8mb4_unicode_ci NULL'
        );

        $customerRecoveryRequest = [
            'customerRecovery' => [
                'id' => 'fffd09464d864770b9ce54e4e8601d7c',
                'customerId' => '51555310038a427a8fcc361433d5784e',
                'hash' => 'Rx33F2DRjmJDHg19tFhtTgMq8tFq0fMF',
                'customer' => [
                    'groupId' => 'cfbd5018d38d41d8adca10d94fc8bdd6',
                    'defaultPaymentMethodId' => 'cf7892d60b794b65b7badae58462715b',
                    'salesChannelId' => '98432def39fc4624b33213a56b8c944d',
                    'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
                    'lastPaymentMethodId' => null,
                    'defaultBillingAddressId' => '85f5178d031147fc9847d99b3bb7d5e0',
                    'defaultShippingAddressId' => 'f5299cbf329d4bd1a092f32bb59b0c31',
                    'customerNumber' => '1337',
                    'salutationId' => '44706b43d8bd4b34a4582185c3fd07f1',
                    'name' => 'Max',
                    'company' => null,
                    'email' => 'test@example.com',
                    'title' => null,
                    'vatIds' => null,
                    'affiliateCode' => null,
                    'campaignCode' => null,
                    'active' => true,
                    'doubleOptInRegistration' => false,
                    'doubleOptInEmailSentDate' => null,
                    'doubleOptInConfirmDate' => null,
                    'hash' => null,
                    'guest' => false,
                    'firstLogin' => null,
                    'lastLogin' => '2021-03-29T08:52:46.863+00:00',
                    'newsletter' => false,
                    'birthday' => null,
                    'lastOrderDate' => '2021-03-29T12:34:22.655+00:00',
                    'orderCount' => 18,
                    'createdAt' => '2021-03-09T09:45:15.280+00:00',
                    'updatedAt' => '2021-03-29T12:34:22.916+00:00',
                    'legacyEncoder' => null,
                    'legacyPassword' => null,
                    'group' => null,
                    'defaultPaymentMethod' => null,
                    'salesChannel' => null,
                    'language' => null,
                    'lastPaymentMethod' => null,
                    'salutation' => null,
                    'defaultBillingAddress' => null,
                    'defaultShippingAddress' => null,
                    'activeBillingAddress' => null,
                    'activeShippingAddress' => null,
                    'addresses' => null,
                    'orderCustomers' => null,
                    'autoIncrement' => 61,
                    'tags' => null,
                    'tagIds' => null,
                    'promotions' => null,
                    'recoveryCustomer' => null,
                    'customFields' => null,
                    'productReviews' => null,
                    'remoteAddress' => '::',
                    'requestedGroupId' => null,
                    'requestedGroup' => null,
                    'boundSalesChannelId' => null,
                    'boundSalesChannel' => null,
                    'wishlists' => null,
                    '_uniqueIdentifier' => '51555310038a427a8fcc361433d5784e',
                    'versionId' => null,
                    'translated' => [
                    ],
                    'extensions' => [
                        'internal_mapping_storage' => [
                            'apiAlias' => null,
                            'extensions' => [
                            ],
                        ],
                        'foreignKeys' => [
                            'apiAlias' => null,
                            'extensions' => [
                            ],
                        ],
                    ],
                    'id' => '51555310038a427a8fcc361433d5784e',
                ],
                '_uniqueIdentifier' => 'fffd09464d864770b9ce54e4e8601d7c',
                'versionId' => null,
                'translated' => [
                ],
                'createdAt' => '2021-03-29T12:38:47.091+00:00',
                'updatedAt' => null,
                'extensions' => [
                    'foreignKeys' => [
                        'apiAlias' => null,
                        'extensions' => [
                        ],
                    ],
                ],
            ],
            'resetUrl' => 'http://localhost/development/public/account/recover/password?hash=Rx33F2DRjmJDHg19tFhtTgMq8tFq0fMF',
            'shopName' => 'Storefront',
            'salesChannel' => [
                'typeId' => '8a243080f92e4c719546314b577cf82b',
                'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
                'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                'paymentMethodId' => 'cf7892d60b794b65b7badae58462715b',
                'shippingMethodId' => '71ebb873740e4f44a9f49a3229961a69',
                'countryId' => 'c0da63f63ceb4d8ebaa5874dbe48f5dc',
                'navigationCategoryId' => 'edffcfe389e84a5aaa40c56874f97e39',
                'navigationCategoryDepth' => 2,
                'homeSlotConfig' => null,
                'homeCmsPageId' => null,
                'homeCmsPage' => null,
                'homeEnabled' => null,
                'homeName' => null,
                'homeMetaTitle' => null,
                'homeMetaDescription' => null,
                'homeKeywords' => null,
                'footerCategoryId' => null,
                'serviceCategoryId' => null,
                'name' => 'Storefront',
                'shortName' => null,
                'accessKey' => 'SWSCAETKEFPZSJJHZVBNQ2D6YG',
                'currencies' => null,
                'languages' => null,
                'configuration' => null,
                'active' => true,
                'maintenance' => false,
                'maintenanceIpWhitelist' => null,
                'taxCalculationType' => 'horizontal',
                'type' => null,
                'currency' => null,
                'language' => null,
                'paymentMethod' => null,
                'shippingMethod' => null,
                'country' => null,
                'orders' => null,
                'customers' => null,
                'countries' => null,
                'paymentMethods' => null,
                'shippingMethods' => null,
                'translations' => null,
                'domains' => [
                    0 => [
                        'url' => 'http://localhost/development/public',
                        'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                        'currency' => null,
                        'snippetSetId' => '530d80c5293b402d84fe903b9579eb19',
                        'snippetSet' => null,
                        'salesChannelId' => 'e87ba37297a94629abc2be5ea1d0a1e0',
                        'salesChannel' => null,
                        'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
                        'language' => null,
                        'customFields' => null,
                        'productExports' => null,
                        'salesChannelDefaultHreflang' => null,
                        'hreflangUseOnlyLocale' => false,
                        '_uniqueIdentifier' => 'c448b63e00d448ca939b311edce409d3',
                        'versionId' => null,
                        'translated' => [
                        ],
                        'createdAt' => '2021-03-09T09:44:27.960+00:00',
                        'updatedAt' => null,
                        'extensions' => [
                            'foreignKeys' => [
                                'apiAlias' => null,
                                'extensions' => [
                                ],
                            ],
                        ],
                        'id' => 'c448b63e00d448ca939b311edce409d3',
                    ],
                ],
                'systemConfigs' => null,
                'customFields' => null,
                'navigationCategory' => null,
                'footerCategory' => null,
                'serviceCategory' => null,
                'productVisibilities' => null,
                'mailHeaderFooterId' => null,
                'numberRangeSalesChannels' => null,
                'mailHeaderFooter' => null,
                'customerGroupId' => 'cfbd5018d38d41d8adca10d94fc8bdd6',
                'customerGroup' => null,
                'newsletterRecipients' => null,
                'promotionSalesChannels' => null,
                'productReviews' => null,
                'seoUrls' => null,
                'seoUrlTemplates' => null,
                'mainCategories' => null,
                'paymentMethodIds' => [
                    0 => 'a4386b473b24419591511f2d60cda25f',
                    1 => 'bfb351a897eb4a699c7c1d6718e1674b',
                    2 => 'cf7892d60b794b65b7badae58462715b',
                    3 => 'eee8328b1c3240a8873fe99723dcdf27',
                ],
                'productExports' => null,
                'hreflangActive' => false,
                'hreflangDefaultDomainId' => null,
                'hreflangDefaultDomain' => null,
                'analyticsId' => null,
                'analytics' => null,
                'customerGroupsRegistrations' => null,
                'eventActions' => null,
                'boundCustomers' => null,
                'wishlists' => null,
                'landingPages' => null,
                '_uniqueIdentifier' => 'e87ba37297a94629abc2be5ea1d0a1e0',
                'versionId' => null,
                'translated' => [
                    'name' => 'Storefront',
                    'customFields' => [
                    ],
                ],
                'createdAt' => '2021-03-09T09:44:27.960+00:00',
                'updatedAt' => null,
                'extensions' => [
                    'foreignKeys' => [
                        'apiAlias' => null,
                        'extensions' => [
                        ],
                    ],
                ],
                'id' => 'e87ba37297a94629abc2be5ea1d0a1e0',
                'navigationCategoryVersionId' => '0fa91ce3e96a4bc2be4bd9ce752c3425',
                'footerCategoryVersionId' => null,
                'serviceCategoryVersionId' => null,
            ],
        ];

        $userRecoveryRequest = [
            'userRecovery' => [
                'id' => 'a778b433026e4fcbb6c43eeeba788bf7',
                'userId' => '2ea03ac62c1146b588830e0ad467c239',
                'hash' => '7vtxAG9bHk1osCgF4BK53Upn9HYoC8P2',
                'user' => [
                    'localeId' => '8d737b02c2b747a1a3ffca3907751d99',
                    'avatarId' => null,
                    'username' => 'admin',
                    'name' => 'Max',
                    'title' => null,
                    'email' => 'testing@example.com',
                    'active' => true,
                    'admin' => true,
                    'aclRoles' => null,
                    'locale' => null,
                    'avatarMedia' => null,
                    'media' => null,
                    'accessKeys' => null,
                    'configs' => null,
                    'stateMachineHistoryEntries' => null,
                    'importExportLogEntries' => null,
                    'recoveryUser' => null,
                    'storeToken' => null,
                    'lastUpdatedPasswordAt' => null,
                    'customFields' => null,
                    'createdOrders' => null,
                    'updatedOrders' => null,
                    '_uniqueIdentifier' => '2ea03ac62c1146b588830e0ad467c239',
                    'versionId' => null,
                    'translated' => [
                    ],
                    'createdAt' => '2021-03-09T09:44:27.222+00:00',
                    'updatedAt' => '2021-03-29T15:25:06.403+00:00',
                    'extensions' => [
                        'internal_mapping_storage' => [
                            'apiAlias' => null,
                            'extensions' => [
                            ],
                        ],
                        'foreignKeys' => [
                            'apiAlias' => null,
                            'extensions' => [
                            ],
                        ],
                    ],
                    'id' => '2ea03ac62c1146b588830e0ad467c239',
                ],
                '_uniqueIdentifier' => 'a778b433026e4fcbb6c43eeeba788bf7',
                'versionId' => null,
                'translated' => [
                ],
                'createdAt' => '2021-03-29T15:25:20.638+00:00',
                'updatedAt' => null,
                'extensions' => [
                    'foreignKeys' => [
                        'apiAlias' => null,
                        'extensions' => [
                        ],
                    ],
                ],
            ],
            'resetUrl' => 'http://localhost/development/public/admin#/login/user-recovery/7vtxAG9bHk1osCgF4BK53Upn9HYoC8P2',
        ];

        $customerRegistration = [
            'customer' => [
                'groupId' => 'cfbd5018d38d41d8adca10d94fc8bdd6',
                'defaultPaymentMethodId' => 'cf7892d60b794b65b7badae58462715b',
                'salesChannelId' => 'e87ba37297a94629abc2be5ea1d0a1e0',
                'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
                'lastPaymentMethodId' => null,
                'defaultBillingAddressId' => 'b8794be7a6d840e99ffc76f17320df2b',
                'defaultShippingAddressId' => 'b8794be7a6d840e99ffc76f17320df2b',
                'customerNumber' => '10060',
                'salutationId' => 'bd3fd9c43c754e02a11d92b7f7cedc4f',
                'name' => 'Max',
                'company' => null,
                'email' => 'testing@example.com',
                'title' => null,
                'vatIds' => null,
                'affiliateCode' => null,
                'campaignCode' => null,
                'active' => true,
                'doubleOptInRegistration' => false,
                'doubleOptInEmailSentDate' => null,
                'doubleOptInConfirmDate' => null,
                'hash' => null,
                'guest' => false,
                'firstLogin' => '2021-03-29T12:53:05.736+00:00',
                'lastLogin' => null,
                'newsletter' => false,
                'birthday' => null,
                'lastOrderDate' => null,
                'orderCount' => 0,
                'createdAt' => '2021-03-29T12:53:05.958+00:00',
                'updatedAt' => null,
                'legacyEncoder' => null,
                'legacyPassword' => null,
                'group' => null,
                'defaultPaymentMethod' => null,
                'salesChannel' => null,
                'language' => null,
                'lastPaymentMethod' => null,
                'salutation' => [
                    'salutationKey' => 'mr',
                    'displayName' => 'Mr.',
                    'letterName' => 'Dear Mr.',
                    'translations' => null,
                    'customers' => null,
                    'customerAddresses' => null,
                    'orderCustomers' => null,
                    'orderAddresses' => null,
                    'newsletterRecipients' => null,
                    '_uniqueIdentifier' => 'bd3fd9c43c754e02a11d92b7f7cedc4f',
                    'versionId' => null,
                    'translated' => [
                        'displayName' => 'Mr.',
                        'letterName' => 'Dear Mr.',
                    ],
                    'createdAt' => '2021-03-09T09:43:59.659+00:00',
                    'updatedAt' => null,
                    'extensions' => [
                        'internal_mapping_storage' => [
                            'apiAlias' => null,
                            'extensions' => [
                            ],
                        ],
                        'foreignKeys' => [
                            'apiAlias' => null,
                            'extensions' => [
                            ],
                        ],
                    ],
                    'id' => 'bd3fd9c43c754e02a11d92b7f7cedc4f',
                ],
                'defaultBillingAddress' => null,
                'defaultShippingAddress' => null,
                'activeBillingAddress' => null,
                'activeShippingAddress' => null,
                'addresses' => [
                    0 => [
                        'customerId' => 'c3445cbcbcec4678b3f15639a892afd1',
                        'countryId' => 'c0da63f63ceb4d8ebaa5874dbe48f5dc',
                        'countryStateId' => null,
                        'salutationId' => 'bd3fd9c43c754e02a11d92b7f7cedc4f',
                        'name' => 'Max',
                        'zipcode' => '12345',
                        'city' => 'Musterstadt',
                        'company' => null,
                        'department' => null,
                        'title' => null,
                        'street' => 'Musterstr. 2',
                        'phoneNumber' => null,
                        'additionalAddressLine1' => null,
                        'additionalAddressLine2' => null,
                        'country' => null,
                        'countryState' => null,
                        'salutation' => null,
                        'customer' => null,
                        'customFields' => null,
                        '_uniqueIdentifier' => 'b8794be7a6d840e99ffc76f17320df2b',
                        'versionId' => null,
                        'translated' => [
                        ],
                        'createdAt' => '2021-03-29T12:53:05.958+00:00',
                        'updatedAt' => null,
                        'extensions' => [
                            'foreignKeys' => [
                                'apiAlias' => null,
                                'extensions' => [
                                ],
                            ],
                        ],
                        'id' => 'b8794be7a6d840e99ffc76f17320df2b',
                    ],
                ],
                'orderCustomers' => null,
                'autoIncrement' => 62,
                'tags' => null,
                'tagIds' => null,
                'promotions' => null,
                'recoveryCustomer' => null,
                'customFields' => null,
                'productReviews' => null,
                'remoteAddress' => null,
                'requestedGroupId' => null,
                'requestedGroup' => null,
                'boundSalesChannelId' => null,
                'boundSalesChannel' => null,
                'wishlists' => null,
                '_uniqueIdentifier' => 'c3445cbcbcec4678b3f15639a892afd1',
                'versionId' => null,
                'translated' => [
                ],
                'extensions' => [
                    'foreignKeys' => [
                        'apiAlias' => null,
                        'extensions' => [
                        ],
                    ],
                ],
                'id' => 'c3445cbcbcec4678b3f15639a892afd1',
            ],
            'salesChannel' => [
                'typeId' => '8a243080f92e4c719546314b577cf82b',
                'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
                'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                'paymentMethodId' => 'cf7892d60b794b65b7badae58462715b',
                'shippingMethodId' => '71ebb873740e4f44a9f49a3229961a69',
                'countryId' => 'c0da63f63ceb4d8ebaa5874dbe48f5dc',
                'navigationCategoryId' => 'edffcfe389e84a5aaa40c56874f97e39',
                'navigationCategoryDepth' => 2,
                'homeSlotConfig' => null,
                'homeCmsPageId' => null,
                'homeCmsPage' => null,
                'homeEnabled' => null,
                'homeName' => null,
                'homeMetaTitle' => null,
                'homeMetaDescription' => null,
                'homeKeywords' => null,
                'footerCategoryId' => null,
                'serviceCategoryId' => null,
                'name' => 'Storefront',
                'shortName' => null,
                'accessKey' => 'SWSCAETKEFPZSJJHZVBNQ2D6YG',
                'currencies' => null,
                'languages' => null,
                'configuration' => null,
                'active' => true,
                'maintenance' => false,
                'maintenanceIpWhitelist' => null,
                'taxCalculationType' => 'horizontal',
                'type' => null,
                'currency' => null,
                'language' => null,
                'paymentMethod' => null,
                'shippingMethod' => null,
                'country' => null,
                'orders' => null,
                'customers' => null,
                'countries' => null,
                'paymentMethods' => null,
                'shippingMethods' => null,
                'translations' => null,
                'domains' => [
                    0 => [
                        'url' => 'http://localhost/development/public',
                        'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
                        'currency' => null,
                        'snippetSetId' => '530d80c5293b402d84fe903b9579eb19',
                        'snippetSet' => null,
                        'salesChannelId' => 'e87ba37297a94629abc2be5ea1d0a1e0',
                        'salesChannel' => null,
                        'languageId' => '2fbb5fe2e29a4d70aa5854ce7ce3e20b',
                        'language' => null,
                        'customFields' => null,
                        'productExports' => null,
                        'salesChannelDefaultHreflang' => null,
                        'hreflangUseOnlyLocale' => false,
                        '_uniqueIdentifier' => 'c448b63e00d448ca939b311edce409d3',
                        'versionId' => null,
                        'translated' => [
                        ],
                        'createdAt' => '2021-03-09T09:44:27.960+00:00',
                        'updatedAt' => null,
                        'extensions' => [
                            'foreignKeys' => [
                                'apiAlias' => null,
                                'extensions' => [
                                ],
                            ],
                        ],
                        'id' => 'c448b63e00d448ca939b311edce409d3',
                    ],
                ],
                'systemConfigs' => null,
                'customFields' => null,
                'navigationCategory' => null,
                'footerCategory' => null,
                'serviceCategory' => null,
                'productVisibilities' => null,
                'mailHeaderFooterId' => null,
                'numberRangeSalesChannels' => null,
                'mailHeaderFooter' => null,
                'customerGroupId' => 'cfbd5018d38d41d8adca10d94fc8bdd6',
                'customerGroup' => null,
                'newsletterRecipients' => null,
                'promotionSalesChannels' => null,
                'productReviews' => null,
                'seoUrls' => null,
                'seoUrlTemplates' => null,
                'mainCategories' => null,
                'paymentMethodIds' => [
                    0 => 'a4386b473b24419591511f2d60cda25f',
                    1 => 'bfb351a897eb4a699c7c1d6718e1674b',
                    2 => 'cf7892d60b794b65b7badae58462715b',
                    3 => 'eee8328b1c3240a8873fe99723dcdf27',
                ],
                'productExports' => null,
                'hreflangActive' => false,
                'hreflangDefaultDomainId' => null,
                'hreflangDefaultDomain' => null,
                'analyticsId' => null,
                'analytics' => null,
                'customerGroupsRegistrations' => null,
                'eventActions' => null,
                'boundCustomers' => null,
                'wishlists' => null,
                'landingPages' => null,
                '_uniqueIdentifier' => 'e87ba37297a94629abc2be5ea1d0a1e0',
                'versionId' => null,
                'translated' => [
                    'name' => 'Storefront',
                    'customFields' => [
                    ],
                ],
                'createdAt' => '2021-03-09T09:44:27.960+00:00',
                'updatedAt' => null,
                'extensions' => [
                    'foreignKeys' => [
                        'apiAlias' => null,
                        'extensions' => [
                        ],
                    ],
                ],
                'id' => 'e87ba37297a94629abc2be5ea1d0a1e0',
                'navigationCategoryVersionId' => '0fa91ce3e96a4bc2be4bd9ce752c3425',
                'footerCategoryVersionId' => null,
                'serviceCategoryVersionId' => null,
            ],
        ];

        $connection->executeStatement('UPDATE `mail_template_type` SET `template_data` = ? WHERE `mail_template_type`.`technical_name` = \'customer.recovery.request\'', [json_encode($customerRecoveryRequest)]);
        $connection->executeStatement('UPDATE `mail_template_type` SET `template_data` = ? WHERE `mail_template_type`.`technical_name` = \'user.recovery.request\'', [json_encode($userRecoveryRequest)]);
        $connection->executeStatement('UPDATE `mail_template_type` SET `template_data` = ? WHERE `mail_template_type`.`technical_name` = \'customer_register\'', [json_encode($customerRegistration)]);
        $connection->executeStatement('UPDATE `mail_template_type` SET `template_data` = ? WHERE `mail_template_type`.`technical_name` = \'customer_register.double_opt_in\'', [json_encode($customerRegistration)]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
