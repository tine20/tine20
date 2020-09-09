/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.relation');

/**
 * @namespace   Tine.widgets.relation
 * @class       Tine.widgets.relation.GridRenderer
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @extends     Ext.Component
 */
Tine.widgets.relation.GridRenderer = function(config) {
    Ext.apply(this, config);
    Tine.widgets.relation.GridRenderer.superclass.constructor.call(this);
};

Ext.extend(Tine.widgets.relation.GridRenderer, Ext.Component, {
    appName:      null,
    type:         null,
    foreignApp:   null,
    foreignModel: null,
    relModel:     null,
    recordClass:  null,
    
    /**
     * initializes the component
     */
    initComponent: function() {
        Tine.log.debug('Initializing relation renderer with config:');
        Tine.log.debug('appName: ' + this.appName + ', type: ' + this.type + ', foreignApp: ' + this.foreignApp + ', foreignModel: ' + this.foreignModel);
        this.relModel = this.foreignApp + '_Model_' + this.foreignModel;
    },
    
    render: function(data, metadata, ownRecord) {
        var relations = ownRecord.get('relations');

        if ( ! _.get(relations, 'length', 0)) {
            return '';
        }
        
        if (! this.recordClass) {
            if (! Tine[this.foreignApp] || ! Tine[this.foreignApp].Model) {
                Tine.log.warn('Tine.widgets.relation.GridRenderer::render - ForeignApp not found: ' + this.foreignApp);
                return '';
            }
            
            this.recordClass = Tine[this.foreignApp].Model[this.foreignModel];
        }
        
        for (var index = 0; index < relations.length; index++) {
            var el = relations[index];
            if (el.type == this.type && el.related_model == this.relModel) {
                if (!el.related_record) {
                    return i18n._('No Access');
                }
                var record = new this.recordClass(el.related_record);
                return Ext.util.Format.htmlEncode(record.getTitle());
            }
        }
    }
});
