/**
 * @package admin
 *
 * @module core/data/CicadaError
 */
import utils from 'src/core/service/util.service';

/**
 * @class
 * @description Simple data structure to hold information about Api Errors.
 * @memberOf module:core/data/CicadaError
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default class CicadaError {
    constructor({ code, meta = {}, status = '', detail = '' } = {}) {
        if (typeof code !== 'string' || code === '') {
            throw new Error('[CicadaError] can not identify error by code');
        }

        this._id = utils.createId();
        this._code = code;
        this._parameters = meta.parameters;
        this._status = status;
        this._detail = detail;
    }

    get id() {
        return this._id;
    }

    get code() {
        return this._code;
    }

    set code(value) {
        this._code = value;
    }

    get parameters() {
        return this._parameters;
    }

    set parameters(value) {
        this._parameters = value;
    }

    get status() {
        return this._status;
    }

    set status(value) {
        this._status = value;
    }

    get detail() {
        return this._detail;
    }

    set detail(value) {
        this._trace = value;
    }
}
