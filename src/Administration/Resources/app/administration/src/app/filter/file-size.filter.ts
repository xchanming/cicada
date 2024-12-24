/**
 * @package admin
 */

/**
 * @private
 */
Cicada.Filter.register('fileSize', (value: number, locale: string) => {
    if (!value) {
        return '';
    }

    return Cicada.Utils.format.fileSize(value, locale);
});

/* @private */
export {};
