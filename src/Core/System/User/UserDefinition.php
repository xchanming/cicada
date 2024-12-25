<?php declare(strict_types=1);

namespace Cicada\Core\System\User;

use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\Api\Acl\Role\AclRoleDefinition;
use Cicada\Core\Framework\Api\Acl\Role\AclUserRoleDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\EntityProtectionCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityProtection\WriteProtection;
use Cicada\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Cicada\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\EmailField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\FkField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\SetNullOnDelete;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IdField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\StringField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\TimeZoneField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Locale\LocaleDefinition;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineHistory\StateMachineHistoryDefinition;
use Cicada\Core\System\User\Aggregate\UserAccessKey\UserAccessKeyDefinition;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigDefinition;
use Cicada\Core\System\User\Aggregate\UserRecovery\UserRecoveryDefinition;

#[Package('services-settings')]
class UserDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = 'user';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return UserCollection::class;
    }

    public function getEntityClass(): string
    {
        return UserEntity::class;
    }

    public function since(): ?string
    {
        return '6.0.0.0';
    }

    public function getDefaults(): array
    {
        return [
            'timeZone' => 'UTC',
        ];
    }

    protected function defineProtections(): EntityProtectionCollection
    {
        return new EntityProtectionCollection([new WriteProtection(Context::SYSTEM_SCOPE)]);
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('locale_id', 'localeId', LocaleDefinition::class))->addFlags(new Required()),
            (new StringField('username', 'username'))->addFlags(new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new PasswordField('password', 'password', \PASSWORD_DEFAULT, [], PasswordField::FOR_ADMIN))->removeFlag(ApiAware::class)->addFlags(new Required()),
            (new StringField('name', 'name'))->addFlags(new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new StringField('phone', 'phone'))->addFlags(new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new StringField('title', 'title'))->addFlags(new SearchRanking(SearchRanking::MIDDLE_SEARCH_RANKING)),
            (new EmailField('email', 'email'))->addFlags(new Required(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            new BoolField('active', 'active'),
            new BoolField('admin', 'admin'),
            new DateTimeField('last_updated_password_at', 'lastUpdatedPasswordAt'),
            (new TimeZoneField('time_zone', 'timeZone'))->addFlags(new Required()),
            new CustomFields(),
            new ManyToOneAssociationField('locale', 'locale_id', LocaleDefinition::class, 'id', false),
            new FkField('avatar_id', 'avatarId', MediaDefinition::class),
            new ManyToOneAssociationField('avatarMedia', 'avatar_id', MediaDefinition::class),
            (new OneToManyAssociationField('media', MediaDefinition::class, 'user_id'))->addFlags(new SetNullOnDelete()),
            (new OneToManyAssociationField('accessKeys', UserAccessKeyDefinition::class, 'user_id', 'id'))->addFlags(new CascadeDelete()),
            (new OneToManyAssociationField('configs', UserConfigDefinition::class, 'user_id', 'id'))->addFlags(new CascadeDelete()),
            new OneToManyAssociationField('stateMachineHistoryEntries', StateMachineHistoryDefinition::class, 'user_id', 'id'),
            (new OneToManyAssociationField('importExportLogEntries', ImportExportLogDefinition::class, 'user_id', 'id'))->addFlags(new SetNullOnDelete()),
            new ManyToManyAssociationField('aclRoles', AclRoleDefinition::class, AclUserRoleDefinition::class, 'user_id', 'acl_role_id'),
            new OneToOneAssociationField('recoveryUser', 'id', 'user_id', UserRecoveryDefinition::class, false),
            (new StringField('store_token', 'storeToken'))->removeFlag(ApiAware::class),
            new OneToManyAssociationField('createdOrders', OrderDefinition::class, 'created_by_id', 'id'),
            new OneToManyAssociationField('updatedOrders', OrderDefinition::class, 'updated_by_id', 'id'),
            new OneToManyAssociationField('createdCustomers', CustomerDefinition::class, 'created_by_id', 'id'),
            new OneToManyAssociationField('updatedCustomers', CustomerDefinition::class, 'updated_by_id', 'id'),
        ]);
    }
}
