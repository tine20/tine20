export default (LIVR) => {
    return (fieldPath) => {
        const pathParts = String(fieldPath).split('.');

        return (value, params, outputArr) => {
            _.each(pathParts, (field) => {
                params = _.get(params, field, null);
            });
            if (value === params) {
                return 'FIELDS_EQUAL';
            }
        }
    };
}
