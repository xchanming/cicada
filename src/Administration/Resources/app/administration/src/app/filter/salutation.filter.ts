/**
 * @sw-package framework
 */

const { Filter, Defaults } = Cicada;

type SalutationType = {
    id: string;
    salutationKey: string;
    displayName: string;
};

Filter.register(
    'salutation',
    (
        entity: {
            salutation: SalutationType;
            title: string;
            name: string;
            [key: string]: unknown;
        },
        fallbackSnippet = '',
    ): string => {
        if (!entity) {
            return fallbackSnippet;
        }

        let hideSalutation = true;

        if (entity.salutation && entity.salutation.id !== Defaults.defaultSalutationId) {
            hideSalutation = [
                'not_specified',
            ].some((item) => item === entity.salutation.salutationKey);
        }

        const params = {
            salutation: !hideSalutation ? entity.salutation.displayName : '',
            title: entity.title || '',
            name: entity.name || '',
        };

        const fullName = Object.values(params).join(' ').replace(/\s+/g, ' ').trim();

        if (fullName === '') {
            return fallbackSnippet;
        }

        return fullName;
    },
);

/**
 * @private
 */
export default {};
