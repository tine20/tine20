export default (LIVR) => {
    return (livr, ruleBuilders) => {
        const conditionValidator = new LIVR.Validator(livr['condition']).registerRules(ruleBuilders).prepare();
        const thenValidator = new LIVR.Validator(livr['then']).registerRules(ruleBuilders).prepare();

        let elseValidator = null
        if (livr['else']) {
            elseValidator = new LIVR.Validator(livr['else']).registerRules(ruleBuilders).prepare();
        }

        return (value, params, outputArr) => {
            let nextValidator = null;
            if (false !== conditionValidator.validate(params)) {
                nextValidator = thenValidator;
            } else if (elseValidator) {
                nextValidator = elseValidator;
            }

            if (nextValidator) {
                if (false === nextValidator.validate(params)) {
                    return Object.values(nextValidator.getErrors()).join(',');
                }
            }
        };
    }
}
