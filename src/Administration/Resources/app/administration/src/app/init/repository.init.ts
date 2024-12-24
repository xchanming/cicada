/**
 * @package admin
 */

const RepositoryFactory = Cicada.Classes._private.RepositoryFactory;
const { EntityHydrator, ChangesetGenerator, EntityFactory } = Cicada.Data;
const ErrorResolverError = Cicada.Data.ErrorResolver;

const customEntityTypes = [
    {
        name: 'custom_entity_detail',
        icon: 'regular-image-text',
        // eslint-disable-next-line no-warning-comments
        // ToDo NEXT-22655 - Re-implement, when custom_entity_list page is available
        // }, {
        //     name: 'custom_entity_list',
        //     icon: 'regular-list',
    },
];

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default function initializeRepositoryFactory(container: InitContainer) {
    const httpClient = container.httpClient;
    const factoryContainer = Cicada.Application.getContainer('factory');
    const serviceContainer = Cicada.Application.getContainer('service');

    return httpClient
        .get('_info/entity-schema.json', {
            headers: {
                Authorization: `Bearer ${serviceContainer.loginService.getToken()}`,
            },
        })
        .then(({ data }) => {
            const entityDefinitionFactory = factoryContainer.entityDefinition;
            const customEntityDefinitionService = serviceContainer.customEntityDefinitionService;
            const cmsPageTypeService = serviceContainer.cmsPageTypeService;
            let hasCmsAwareDefinitions = false;

            // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
            Object.entries(data).forEach(
                ([
                    key,
                    value,
                ]) => {
                    entityDefinitionFactory.add(key, value);

                    if (key.startsWith('custom_entity_') || key.startsWith('ce_')) {
                        // @ts-expect-error - value is defined
                        customEntityDefinitionService.addDefinition(value);
                        // @ts-expect-error - value is defined
                        // eslint-disable-next-line @typescript-eslint/no-unsafe-member-access
                        hasCmsAwareDefinitions = hasCmsAwareDefinitions || !!value?.flags?.['cms-aware'];
                    }
                },
            );

            if (hasCmsAwareDefinitions) {
                customEntityTypes.forEach((customEntityType) => {
                    cmsPageTypeService.register(customEntityType);
                });
            }

            const hydrator = new EntityHydrator();
            const changesetGenerator = new ChangesetGenerator();
            const entityFactory = new EntityFactory();
            const errorResolver = new ErrorResolverError();

            Cicada.Application.addServiceProvider('repositoryFactory', () => {
                return new RepositoryFactory(hydrator, changesetGenerator, entityFactory, httpClient, errorResolver);
            });
            Cicada.Application.addServiceProvider('entityHydrator', () => {
                return hydrator;
            });
            Cicada.Application.addServiceProvider('entityFactory', () => {
                return entityFactory;
            });
        });
}
