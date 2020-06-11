/* global Tine.Tinebase.Model.ImportExportDefinition */

Ext.ns('Tine.Tinebase.Model');

Tine.Tinebase.Model.ImportExportDefinitionMixin = {
    statics: {
        /**
         * get definition by id
         * 
         * @param {String|Tine.Tinebase.Application} app
         * @param {String} id
         * @return {Tine.Tinebase.Model.ImportExportDefinition}
         */
        get: function(app, id) {
            app = _.isString(app) ? Tine.Tinebase.appMgr.get(app) : app;
            
            const allDefinitions = _.get(app.getRegistry().get('exportDefinitions'), 'results', []);
            const definition = _.find(allDefinitions, {id: id});
            
            return Tine.Tinebase.data.Record.setFromJson(definition, Tine.Tinebase.Model.ImportExportDefinition);
        }
    },

    /**
     * check if defined options are missing
     * 
     * @return {Boolean}
     */
    optionsMissing: function(options) {
        return _.reduce(_.get(this, 'data.plugin_options_definition', {}), (result, optionDef, name) => {
            const value = _.get(options, name, _.get(this, 'data.plugin_options_json.' + name));
            const allowEmpty = _.get(optionDef, 'allowEmpty', false);
            
            return result || (!value && !allowEmpty);
        }, false);
    }
};
