/**
 * @package services-settings
 */
import template from './sw-users-permissions-role-view-general.html.twig';

const { mapPropertyErrors } = Cicada.Component.getComponentHelper();

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'acl',
    ],

    props: {
        role: {
            type: Object,
            required: true,
        },
    },

    computed: {
        ...mapPropertyErrors('role', [
            'name',
            'description',
        ]),
    },
};
