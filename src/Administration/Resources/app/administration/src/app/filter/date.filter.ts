/**
 * @package admin
 */

Cicada.Filter.register('date', (value: string, options: Intl.DateTimeFormatOptions = {}): string => {
    if (!value) {
        return '';
    }

    return Cicada.Utils.format.date(value, options);
});

/**
 * @private
 */
export default {};
