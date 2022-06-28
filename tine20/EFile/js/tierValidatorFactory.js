import getIfRule from './LIVR/if'
import getNotEqualToFieldPath from './LIVR/notEqualToFieldPath'

let validator;

export default async () => {
    if (! validator) {
        const LIVR = await import(/* webpackChunkName: "Tinebase/js/livr" */ 'livr');

        LIVR.Validator.registerDefaultRules({
            'if': getIfRule(LIVR),
            'notEqualToFieldPath': getNotEqualToFieldPath(LIVR)
        });
        validator = new LIVR.Validator(Tine.EFile.registry.get('EFileNodeLIVR'));
    }
    
    return validator;
}
