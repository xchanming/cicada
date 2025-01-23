/**
 * @sw-package inventory
 */
import template from './sw-bulk-edit-order-documents-generate-delivery-note.html.twig';

const { State } = Cicada;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default {
    template,

    compatConfig: Cicada.compatConfig,

    computed: {
        generateData: {
            get() {
                return State.get('swBulkEdit').orderDocuments?.delivery_note?.value;
            },
            set(generateData) {
                State.commit('swBulkEdit/setOrderDocumentsValue', {
                    type: 'delivery_note',
                    value: generateData,
                });
            },
        },
    },
};
