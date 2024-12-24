const { Application } = Cicada;

/**
 * @package admin
 *
 * @module core/service/cicada-updates-listener
 */

/**
 *
 * @memberOf module:core/service/cicada-updates-listener
 * @method addCicadaUpdatesListener
 * @param loginService
 * @param serviceContainer
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default function addCicadaUpdatesListener(loginService, serviceContainer) {
    /** @var {String} localStorage token */
    let applicationRoot = null;

    loginService.addOnLoginListener(() => {
        if (!Cicada.Service('acl').can('system.core_update')) {
            return;
        }

        serviceContainer.updateService
            .checkForUpdates()
            .then((response) => {
                if (response.version) {
                    createUpdatesAvailableNotification(response);
                }
            })
            .catch();
    });

    function createUpdatesAvailableNotification(response) {
        const cancelLabel = getApplicationRootReference().$tc('global.default.cancel');
        const updateLabel = getApplicationRootReference().$tc(
            'global.notification-center.cicada-updates-listener.updateNow',
        );

        const notification = {
            title: getApplicationRootReference().$t(
                'global.notification-center.cicada-updates-listener.updatesAvailableTitle',
                {
                    version: response.version,
                },
            ),
            message: getApplicationRootReference().$t(
                'global.notification-center.cicada-updates-listener.updatesAvailableMessage',
                {
                    version: response.version,
                },
            ),
            variant: 'info',
            growl: true,
            system: true,
            actions: [
                {
                    label: updateLabel,
                    route: { name: 'sw.settings.cicada.updates.wizard' },
                },
                {
                    label: cancelLabel,
                },
            ],
            autoClose: false,
        };

        Cicada.State.dispatch('notification/createNotification', notification);
    }

    function getApplicationRootReference() {
        if (!applicationRoot) {
            applicationRoot = Application.getApplicationRoot();
        }

        return applicationRoot;
    }
}
