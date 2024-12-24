/**
 * @package admin
 */
import type { CurrencyOptions } from 'src/core/service/utils/format.utils';

const { currency } = Cicada.Utils.format;

/**
 * @private
 */
Cicada.Filter.register(
    'currency',
    (value: string | boolean, format: string, decimalPlaces: number, additionalOptions: CurrencyOptions) => {
        if (
            (!value || value === true) &&
            (!Cicada.Utils.types.isNumber(value) || Cicada.Utils.types.isEqual(value, NaN))
        ) {
            return '-';
        }

        if (Cicada.Utils.types.isEqual(parseInt(value, 10), NaN)) {
            return value;
        }

        return currency(parseFloat(value), format, decimalPlaces, additionalOptions);
    },
);
