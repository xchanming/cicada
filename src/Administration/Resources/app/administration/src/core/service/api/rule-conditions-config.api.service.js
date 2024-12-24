import ApiService from '../api.service';

/**
 * @private
 * @package services-settings
 */
export default class RuleConditionsConfigApiService extends ApiService {
    constructor(httpClient, loginService) {
        super(httpClient, loginService, null, 'application/json');
        this.name = 'ruleConditionsConfigApiService';
    }

    load() {
        if (Cicada.State.getters['ruleConditionsConfig/getConfig']() !== null) {
            return Promise.resolve();
        }

        return this.httpClient
            .get('_info/rule-config', {
                headers: this.getBasicHeaders(),
            })
            .then((response) => {
                Cicada.State.commit('ruleConditionsConfig/setConfig', ApiService.handleResponse(response));
            });
    }
}
