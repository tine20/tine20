/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.ExampleApplication');

/**
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.Application
 * @extends     Tine.Tinebase.Application
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.ExampleApplication.Application = Ext.extend(Tine.Tinebase.Application, {
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.gettext('Example Application test');
    }
});

/**
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.MainScreen
 * @extends     Tine.widgets.MainScreen
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.ExampleApplication.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'ExampleRecord'
});
    
/**
 * @namespace   Tine.ExampleApplication
 * @class       Tine.ExampleApplication.ExampleRecordTreePanel
 * @extends     Tine.widgets.container.TreePanel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.ExampleApplication.ExampleRecordTreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
    id: 'ExampleApplication_Tree',
    filterMode: 'filterToolbar',
    recordClass: Tine.ExampleApplication.Model.ExampleRecord
});

/**
 * favorites panel
 * 
 * @class       Tine.ExampleApplication.ExampleRecordFilterPanel
 * @extends     Tine.widgets.persistentfilter.PickerPanel
 *  
 * @param {Object} config
 */
Tine.ExampleApplication.ExampleRecordFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.ExampleApplication.ExampleRecordFilterPanel.superclass.constructor.call(this);
};
Ext.extend(Tine.ExampleApplication.ExampleRecordFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'ExampleApplication_Model_ExampleRecordFilter'}]
});
