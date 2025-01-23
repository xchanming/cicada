import Criteria from '@cicada-ag/meteor-admin-sdk/es/data/Criteria';
import template from './sw-category-detail-custom-entity.html.twig';
import './sw-category-detail-custom-entity.scss';

const { Utils } = Cicada;
const EXTENSION_POSTFIX = 'SwCategories';

/**
 * @private
 * @sw-package discovery
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'repositoryFactory',
        'acl',
    ],

    data() {
        return {
            categoryCustomEntityProperty: '',
        };
    },

    props: {
        isLoading: {
            type: Boolean,
            required: false,
            default: false,
        },
    },

    computed: {
        customEntityAssignments(): EntityCollection<'custom_entity'> | undefined {
            return this.category?.extensions?.[`${this.categoryCustomEntityProperty}${EXTENSION_POSTFIX}`] as
                | EntityCollection<'custom_entity'>
                | undefined;
        },

        customEntityColumns(): {
            dataIndex: string;
            property: string;
            label: string;
        }[] {
            return [
                {
                    dataIndex: 'cmsAwareTitle',
                    property: 'cmsAwareTitle',
                    label: this.$tc('sw-category.base.customEntity.instanceAssignment.title'),
                },
            ];
        },

        category(): Entity<'category'> | null {
            // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
            return Cicada.State.get('swCategoryDetail').category as Entity<'category'> | null;
        },

        customEntityCriteria(): Criteria {
            return new Criteria(1, 10).addFilter(Criteria.contains('flags', 'cms-aware'));
        },

        sortingCriteria(): Criteria {
            return new Criteria(1, 10).addSorting(Criteria.sort('cmsAwareTitle', 'ASC'));
        },

        assetFilter() {
            return Cicada.Filter.getByName('asset');
        },
    },

    created(): void {
        void this.fetchCustomEntityName();
    },

    methods: {
        onAssignmentChange(customEntityAssignments: EntityCollection<'custom_entity'>): void {
            const categoryExtensions = this.category?.extensions;
            if (!categoryExtensions) {
                return;
            }

            categoryExtensions[`${this.categoryCustomEntityProperty}${EXTENSION_POSTFIX}`] = customEntityAssignments;
        },

        onEntityChange(id: string, entity?: Entity<'custom_entity'>) {
            if (!this.category) {
                return;
            }

            this.category.customEntityTypeId = id;

            this.categoryCustomEntityProperty = Utils.string.camelCase(entity?.name ?? '');
        },

        async fetchCustomEntityName(): Promise<void> {
            if (!this.category?.customEntityTypeId) {
                return;
            }

            const customEntityRepository = this.repositoryFactory.create('custom_entity');
            const customEntity = await customEntityRepository.get(this.category.customEntityTypeId);

            if (!customEntity) {
                return;
            }

            this.categoryCustomEntityProperty = Utils.string.camelCase(customEntity.name);
        },
    },
});
