/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.widgets.relation');

/**
 * manages renderers
 * 
 * @namespace   Tine.widgets.relation
 * @class       Tine.widgets.relation.Renderer
 * @singleton
 */
Tine.widgets.relation.Renderer = function(){
    var renderers = {};
    
    return {
        renderAll: function(relations) {
            var html = '';

            Ext.each(relations, function(relation) {

                var relatedRecord = relation.related_record,
                    relatedModel = relation.related_model.split('_Model_'),
                    app = Tine.Tinebase.appMgr.get(relatedModel[0]),
                    model = relatedModel[1],
                    relatedModel = Tine[relatedModel[0]].Model[relatedModel[1]],
                    record = new relatedModel(relatedRecord);

                html += '<div class="customfield-rendered-row print-single-details-row">' +
                    '<span class="customfield-rendered-label">'+ app.i18n._(model) + '</span>' +
                    '<span class="customfield-rendered-value">'+ record.getTitle() + '</span>' +
                '</div>';
            }, this);

            return html;
        },

        /**
         * register a custom renderer
         * 
         * @param {String/Application}  app
         * @param {Record}              cfConfig 
         * @param {Function}            renderer
         */
        register: function(app, relations, renderer) {
            var key = appName + record.id;
                
            renderers[key] = renderer;
        }
    }
}();
