/**
 * @package admin
 * @deprecated tag:v6.7.0 - Will be replaced with Pinia store
 */

import type { Module } from 'vuex';
import type { AppModuleDefinition } from 'src/core/service/api/app-modules.service';

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export interface CicadaAppsState {
    apps: AppModuleDefinition[];
    selectedIds: string[];
}

const cicadaApps: Module<CicadaAppsState, VuexRootState> = {
    namespaced: true,

    state() {
        return {
            apps: [],
            selectedIds: [],
        };
    },

    mutations: {
        setApps(state, apps: AppModuleDefinition[]) {
            state.apps = apps;
        },

        setSelectedIds(state, selectedIds: string[]) {
            state.selectedIds = selectedIds;
        },
    },
};

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default cicadaApps;
