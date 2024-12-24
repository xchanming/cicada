import type { CicadaClass } from 'src/core/cicada';
import extensionStore from './extensions.store';

/**
 * @package checkout
 * @private
 */
export default function initState(Cicada: CicadaClass): void {
    Cicada.State.registerModule('cicadaExtensions', extensionStore);

    let languageId = Cicada.State.get('session').languageId;
    Cicada.State._store.subscribe(async ({ type }, state): Promise<void> => {
        if (!Cicada.Service('acl').can('system.plugin_maintain')) {
            return;
        }

        if (type === 'setAdminLocale' && state.session.languageId !== '' && languageId !== state.session.languageId) {
            // Always on page load setAdminLocale will be called once. Catch it to not load refresh extensions
            if (languageId === '') {
                languageId = state.session.languageId;
                return;
            }

            languageId = state.session.languageId;
            await Cicada.Service('cicadaExtensionService').updateExtensionData().then();
        }
    });
}
