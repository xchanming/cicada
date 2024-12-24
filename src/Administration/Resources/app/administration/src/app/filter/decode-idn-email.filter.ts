/**
 * @package admin
 */
import { toUnicode } from 'punycode/';

/**
 * @private
 */
Cicada.Filter.register('decode-idn-email', (value: string) => {
    return toUnicode(value);
});
