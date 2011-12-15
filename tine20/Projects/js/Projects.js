/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Projects');

/**
 * @namespace   Tine.Projects
 * @class       Tine.Projects.Application
 * @extends     Tine.Tinebase.Application
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Projects.Application = Ext.extend(Tine.Tinebase.Application, {
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.gettext('Projects');
    },
    
    init: function() {
        new Tine.Projects.AddressbookGridPanelHook({app: this});

    }
});

/**
 * @namespace   Tine.Projects
 * @class       Tine.Projects.MainScreen
 * @extends     Tine.widgets.MainScreen
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Projects.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Project'
});
    
/**
 * @namespace   Tine.Projects
 * @class       Tine.Projects.TreePanel
 * @extends     Tine.widgets.container.TreePanel
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Projects.TreePanel = Ext.extend(Tine.widgets.container.TreePanel, {
    id: 'Projects_Tree',
    filterMode: 'filterToolbar',
    recordClass: Tine.Projects.Model.Project
});

/**
 * @class       Tine.Projects.FilterPanel
 * @extends     Tine.widgets.persistentfilter.PickerPanel
 *  
 * @param {Object} config
 */
Tine.Projects.FilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Projects.FilterPanel.superclass.constructor.call(this);
};
Ext.extend(Tine.Projects.FilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Projects_Model_ProjectFilter'}]
});
