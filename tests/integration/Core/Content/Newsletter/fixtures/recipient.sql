SET @salutationId = (SELECT `id` FROM salutation LIMIT 1);
SET @languageId = (SELECT `id` FROM `language` LIMIT 1);

INSERT INTO `newsletter_recipient`
    (`id`, `email`, `name`, `last_name`, `zip_code`, `city`, `street`, `status`, `hash`, `salutation_id`, `language_id`, `sales_channel_id`, `custom_fields`, `confirmed_at`, `created_at`, `updated_at`)
VALUES (
    UNHEX('b4b45f58088d41289490db956ca19af7'),
    'unit@test.foo',
    'Foo',
    'Bar',
    '12345',
    'TestingCity',
    '21 Test Street',
    'notSet',
    'b4b45f58088d41289490db956ca19af7',
    @salutationId,
    @languageId,
    UNHEX('98432def39fc4624b33213a56b8c944d'),
    NULL,
    NULL,
    ':createdAt',
    NULL
), (
 UNHEX('7912f4de72aa43d792bcebae4eb45c5c'),
    'unit@test.bar',
    'Bar',
    'Foo',
    '12345',
    'TestingCity',
    '42 Test Street',
    'notSet',
    '7912f4de72aa43d792bcebae4eb45c5c',
     @salutationId,
     @languageId,
    UNHEX('98432def39fc4624b33213a56b8c944d'),
    NULL,
    NULL,
    ':createdAt' - INTERVAL 24 DAY,
    NULL
), (
 UNHEX('ee367309f56445bf88ab944c81907951'),
    'foo@bar.test',
    'Unit',
    'Test',
    '12345',
    'TestingCity',
    '13 Test Street',
    'notSet',
    'ee367309f56445bf88ab944c81907951',
     @salutationId,
     @languageId,
    UNHEX('98432def39fc4624b33213a56b8c944d'),
    NULL,
    NULL,
    ':createdAt' - INTERVAL 30 DAY,
    NULL
), (
 UNHEX('9420908cc96b42379ff86fa1e5a6f10b'),
    'test@bar.fooBar',
    'Test',
    'Unit',
    '12345',
    'TestingCity',
    '79 Test Street',
    'notSet',
    '9420908cc96b42379ff86fa1e5a6f10b',
     @salutationId,
     @languageId,
    UNHEX('98432def39fc4624b33213a56b8c944d'),
    NULL,
    NULL,
    ':createdAt' - INTERVAL 31 DAY,
    NULL
), (
 UNHEX('0d095dffd93b48a6b22300a1dad879d3'),
    'barFoo@unit.test',
    '',
    '',
    '',
    '',
    '',
    'notSet',
    '0d095dffd93b48a6b22300a1dad879d3',
     @salutationId,
     @languageId,
    UNHEX('98432def39fc4624b33213a56b8c944d'),
    NULL,
    NULL,
    ':createdAt' - INTERVAL 40 DAY,
    NULL
);
