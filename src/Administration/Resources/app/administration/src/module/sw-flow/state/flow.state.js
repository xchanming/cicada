const { Service } = Cicada;
const { EntityCollection } = Cicada.Data;
const { types } = Cicada.Utils;

/**
 * @private
 * @sw-package after-sales
 * @deprecated tag:v6.7.0 - Will be replaced with Pinia store
 */
export default {
    namespaced: true,

    state: {
        flow: {
            eventName: '',
            sequences: [],
        },
        originFlow: {},
        triggerEvent: {},
        triggerEvents: [],
        triggerActions: [],
        invalidSequences: [],
        stateMachineState: [],
        documentTypes: [],
        mailTemplates: [],
        customFieldSets: [],
        customFields: [],
        customerGroups: [],
        restrictedRules: [],
        appActions: [],
        originAvailableActions: [],
    },

    mutations: {
        setAppActions(state, actions) {
            state.appActions = [
                ...state.appActions,
                ...actions,
            ];
        },

        setFlow(state, flow) {
            state.flow = flow;
            if (flow.config) {
                state.flow.description = flow.config.description;
                state.flow.sequences = flow.config.sequences;
                state.flow.eventName = flow.config.eventName;
            }
        },

        setOriginFlow(state, flow) {
            state.originFlow = {
                ...flow,
                sequences: Array.from(flow.sequences).map((item) => Object.assign(item, {})),
            };
        },

        setTriggerActions(state, actions) {
            state.triggerActions = actions;
        },

        setTriggerEvent(state, event) {
            state.triggerEvent = event;
        },

        setTriggerEvents(state, events) {
            state.triggerEvents = events;
        },

        setEventName(state, eventName) {
            state.flow.eventName = eventName;
        },

        setSequences(state, sequences) {
            state.flow.sequences = sequences;
        },

        addSequence(state, sequence) {
            if (state.flow.sequences instanceof EntityCollection) {
                state.flow.sequences.add(sequence);
                return;
            }

            state.flow.sequences.push(sequence);
        },

        removeSequences(state, sequenceIds) {
            sequenceIds.forEach((sequenceId) => {
                state.flow.sequences.remove(sequenceId);
            });
        },

        updateSequence(state, params) {
            const sequences = state.flow.sequences;
            const sequenceIndex = sequences.findIndex((el) => el.id === params.id);

            let updatedSequence = {
                ...sequences[sequenceIndex],
                ...params,
            };

            updatedSequence = Object.assign(sequences[sequenceIndex], updatedSequence);

            state.flow.sequences = new EntityCollection(sequences.source, sequences.entity, Cicada.Context.api, null, [
                ...sequences.slice(0, sequenceIndex),
                updatedSequence,
                ...sequences.slice(sequenceIndex + 1),
            ]);
        },

        setStateMachineState(state, stateMachineState) {
            state.stateMachineState = stateMachineState;
        },

        setInvalidSequences(state, invalidSequences) {
            state.invalidSequences = invalidSequences;
        },

        setDocumentTypes(state, documentTypes) {
            state.documentTypes = documentTypes;
        },

        setCustomerGroups(state, customerGroups) {
            state.customerGroups = customerGroups;
        },

        setMailTemplates(state, mailTemplates) {
            state.mailTemplates = mailTemplates;
        },

        removeCurrentFlow(state) {
            state.flow = {
                eventName: '',
                sequences: [],
            };
        },

        removeInvalidSequences(state) {
            state.invalidSequences = [];
        },

        removeTriggerEvent(state) {
            state.triggerEvent = {};
        },

        setCustomFieldSets(state, customFieldSet) {
            state.customFieldSets = customFieldSet;
        },

        setCustomFields(state, customField) {
            state.customFields = customField;
        },

        setRestrictedRules(state, rules) {
            state.restrictedRules = rules;
        },
    },

    getters: {
        appActions(state) {
            return state.appActions;
        },

        getSelectedAppAction(state) {
            return (actionName) => {
                return state.appActions?.find((item) => item.name === actionName);
            };
        },

        sequences(state) {
            return state.flow.sequences;
        },

        hasFlowChanged(state) {
            const flow = {
                ...state.flow,
                sequences: Array.from(state.flow.sequences).filter((item) => {
                    if (item.actionName || item.ruleId) {
                        return Object.assign(item, {});
                    }

                    return false;
                }),
            };

            return !types.isEqual(state.originFlow, flow);
        },

        isSequenceEmpty(state) {
            if (!state.flow.sequences.length) {
                return true;
            }

            if (state.flow.sequences.length > 1) {
                return false;
            }

            const firstSequence = state.flow.sequences[0];
            return !firstSequence.actionName && !firstSequence.ruleId;
        },

        availableActions(state) {
            if (!state.triggerEvent || !state.triggerActions) return [];

            const availableActions = [];

            state.triggerActions.forEach((action) => {
                if (!action.requirements.length) {
                    availableActions.push(action.name);
                    return;
                }

                // check if the current active action contains any required keys from an action option.
                const isActive = action.requirements.some((item) => state.triggerEvent?.aware?.includes(item));

                if (!isActive) {
                    return;
                }

                const actionType = Service('flowBuilderService').mapActionType(action.name);

                if (actionType) {
                    // check if the action is already in the available actions list by typeq
                    const hasDuplicateAction = availableActions.find(
                        (option) => Service('flowBuilderService').mapActionType(option) === actionType,
                    );

                    if (hasDuplicateAction !== undefined) {
                        return;
                    }
                }

                availableActions.push(action.name);
            });

            return availableActions;
        },

        mailTemplateIds(state) {
            return state.flow.sequences
                .filter((item) => item.actionName === Service('flowBuilderService').getActionName('MAIL_SEND'))
                .map((item) => item.config?.mailTemplateId);
        },

        customFieldSetIds(state) {
            const service = Service('flowBuilderService');
            return state.flow.sequences
                .filter(
                    (item) =>
                        item.actionName === service.getActionName('SET_CUSTOMER_CUSTOM_FIELD') ||
                        item.actionName === service.getActionName('SET_ORDER_CUSTOM_FIELD') ||
                        item.actionName === service.getActionName('SET_CUSTOMER_GROUP_CUSTOM_FIELD'),
                )
                .map((item) => item.config?.customFieldSetId);
        },

        customFieldIds(state) {
            const service = Service('flowBuilderService');
            return state.flow.sequences
                .filter(
                    (item) =>
                        item.actionName === service.getActionName('SET_CUSTOMER_CUSTOM_FIELD') ||
                        item.actionName === service.getActionName('SET_ORDER_CUSTOM_FIELD') ||
                        item.actionName === service.getActionName('SET_CUSTOMER_GROUP_CUSTOM_FIELD'),
                )
                .map((item) => item.config?.customFieldId);
        },

        actionGroups() {
            return Service('flowBuilderService').getGroups();
        },

        triggerEvents(state) {
            return state.triggerEvents;
        },

        hasAvailableAction: (state) => (actionName) => {
            // This information was originally persisted into the state in the `availableActions` getter.
            // That's an antipattern and caused endless loops in the flow module.
            // Therefore, we need to recalculate the available actions here.
            const getOriginActions = () => {
                const originAvailableActions = [];

                if (!state.triggerEvent || !state.triggerActions) return [];

                state.triggerActions.forEach((action) => {
                    if (!action.requirements.length) {
                        originAvailableActions.push(action.name);
                        return;
                    }

                    // check if the current active action contains any required keys from an action option.
                    const isActive = action.requirements.some((item) => state.triggerEvent?.aware?.includes(item));

                    if (!isActive || originAvailableActions.includes(action.name)) {
                        return;
                    }

                    originAvailableActions.push(action.name);
                });

                return originAvailableActions;
            };
            const originAvailableActions = getOriginActions();

            return originAvailableActions?.some((name) => name === actionName) ?? false;
        },
    },

    actions: {
        resetFlowState({ commit }) {
            commit('removeCurrentFlow');
            commit('removeInvalidSequences');
            commit('removeTriggerEvent');
        },

        setRestrictedRules({ commit }, id) {
            Cicada.Service('ruleConditionDataProviderService')
                .getRestrictedRules(`flowTrigger.${id}`)
                .then((result) => {
                    commit('setRestrictedRules', result);
                });
        },

        fetchTriggerActions({ commit }) {
            Service('businessEventService')
                .getBusinessEvents()
                .then((result) => {
                    commit('setTriggerEvents', result);
                })
                .catch(() => {
                    commit('setTriggerEvents', []);
                });
        },
    },
};
