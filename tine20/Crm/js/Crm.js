/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.Application
 * @extends     Tine.Tinebase.Application
 * Crm Application Object <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Crm.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text _('New Lead')
     */
    addButtonText: 'New Lead',
    
    init: function() {
        Tine.Crm.Application.superclass.init.apply(this, arguments);
        
        new Tine.Crm.AddressbookGridPanelHook({app: this});
    }
});

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.MainScreen
 * @extends Tine.widgets.MainScreen
 * MainScreen of the Crm Application <br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @constructor
 * Constructs mainscreen of the crm application
 */
Tine.Crm.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Lead',
    contentTypes: [
        {model: 'Lead', requiredRight: null, singularContainerMode: false}
        ]
});

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.TreePanel
 * @extends Tine.widgets.container.TreePanel
 * Left Crm Panel including Tree<br>
 * 
 * TODO add d&d support to tree
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.LeadTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'CrmLeadTreePanel';
    this.filterMode = 'filterToolbar';
    this.recordClass = Tine.Crm.Model.Lead;
    Tine.Crm.LeadTreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Crm.LeadTreePanel , Tine.widgets.container.TreePanel);

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.FilterPanel
 * @extends Tine.widgets.persistentfilter.PickerPanel
 * Crm Filter Panel<br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.LeadFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Crm.LeadFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Crm.LeadFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Crm_Model_LeadFilter'}]
});



/**
 * @namespace Tine.Crm
 * @class Tine.Crm.leadBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Lead Backend
 */ 
Tine.Crm.leadBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Crm',
    modelName: 'Lead',
    recordClass: Tine.Crm.Model.Lead
});

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.settingBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * Settings Backend
 * 
 * TODO generalize this
 */ 
Tine.Crm.settingsBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Crm',
    modelName: 'Settings',
    recordClass: Tine.Crm.Model.Settings
});
