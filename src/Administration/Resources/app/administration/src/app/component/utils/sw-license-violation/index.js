import template from './sw-license-violation.html.twig';
import './sw-license-violation.scss';

const { mapState } = Cicada.Component.getComponentHelper();

/**
 * @private
 */
Cicada.Component.register('sw-license-violation', {
    template,

    compatConfig: Cicada.compatConfig,

    inject: [
        'cacheApiService',
        'extensionStoreActionService',
        'licenseViolationService',
        'loginService',
    ],

    mixins: [
        Cicada.Mixin.getByName('notification'),
    ],

    data() {
        return {
            licenseSubscription: null,
            showViolation: false,
            readNotice: false,
            loading: [],
            showDeleteModal: false,
            deletePluginItem: null,
        };
    },

    computed: {
        ...mapState('licenseViolation', [
            'violations',
            'warnings',
        ]),

        visible() {
            if (!this.showViolation) {
                return false;
            }

            return this.violations.length > 0;
        },

        pluginCriteria() {
            return new Cicada.Data.Criteria(1, 50);
        },

        isLoading() {
            return this.loading.length > 0;
        },
    },

    watch: {
        $route: {
            handler() {
                this.$nextTick(() => {
                    this.getPluginViolation();
                });
            },
            immediate: true,
        },
        visible: {
            handler(newValue) {
                if (newValue !== true) {
                    return;
                }

                this.fetchPlugins();
            },
            immediate: true,
        },
    },

    methods: {
        getPluginViolation() {
            if (!this.loginService.isLoggedIn()) {
                return Promise.resolve();
            }

            this.showViolation = this.licenseViolationService.isTimeExpired(
                this.licenseViolationService.key.showViolationsKey,
            );

            this.addLoading('getPluginViolation');

            return this.licenseViolationService
                .checkForLicenseViolations()
                .then(({ violations, warnings, other }) => {
                    Cicada.State.commit('licenseViolation/setViolations', violations);
                    Cicada.State.commit('licenseViolation/setWarnings', warnings);
                    Cicada.State.commit('licenseViolation/setOther', other);
                })
                .finally(() => {
                    this.finishLoading('getPluginViolation');
                });
        },

        reloadViolations() {
            this.licenseViolationService.resetLicenseViolations();

            return this.getPluginViolation();
        },

        deactivateTemporary() {
            this.licenseViolationService.saveTimeToLocalStorage(this.licenseViolationService.key.showViolationsKey);

            this.readNotice = false;
            this.showViolation = this.licenseViolationService.isTimeExpired(
                this.licenseViolationService.key.showViolationsKey,
            );
        },

        fetchPlugins() {
            if (!this.loginService.isLoggedIn()) {
                return;
            }

            this.addLoading('fetchPlugins');

            this.extensionStoreActionService
                .getMyExtensions()
                .then((response) => {
                    this.plugins = response;
                })
                .finally(() => {
                    this.finishLoading('fetchPlugins');
                });
        },

        deletePlugin(violation) {
            this.deletePluginItem = violation;
            this.showDeleteModal = true;
        },

        onCloseDeleteModal() {
            this.deletePluginItem = null;
            this.showDeleteModal = false;
        },

        onConfirmDelete() {
            const violation = this.deletePluginItem;

            this.showDeleteModal = false;
            this.addLoading('deletePlugin');

            const matchingPlugin = this.plugins.find((plugin) => plugin.name === violation.name);

            return this.licenseViolationService
                .forceDeletePlugin(matchingPlugin)
                .then(() => {
                    this.createNotificationSuccess({
                        message: this.$tc('sw-license-violation.successfullyDeleted'),
                    });

                    return this.reloadViolations();
                })
                .finally(() => {
                    this.finishLoading('deletePlugin');
                });
        },

        getPluginForViolation(violation) {
            if (!Array.isArray(this.plugins)) {
                return null;
            }

            const matchingPlugin = this.plugins.find((plugin) => {
                return plugin.name === violation.name;
            });

            return matchingPlugin || null;
        },

        addLoading(key) {
            this.loading.push(key);
        },

        finishLoading(key) {
            this.loading = this.loading.filter((value) => value !== key);
        },
    },
});
