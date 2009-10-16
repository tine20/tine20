/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Crm');

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.MainScreen
 * @extends Tine.Tinebase.widgets.app.MainScreen
 * MainScreen of the Crm Application <br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * @constructor
 * Constructs mainscreen of the crm application
 */
Tine.Crm.MainScreen = Tine.Tinebase.widgets.app.MainScreen;

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
 * @version     $Id$
 */
Tine.Crm.TreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'CrmTreePanel';
    this.recordClass = Tine.Crm.Model.Lead;
    Tine.Crm.TreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Crm.TreePanel , Tine.widgets.container.TreePanel);

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.FilterPanel
 * @extends Tine.widgets.grid.PersistentFilterPicker
 * Crm Filter Panel<br>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
Tine.Crm.FilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Crm.FilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Crm.FilterPanel, Tine.widgets.grid.PersistentFilterPicker, {
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
 * TODO generalize this?
 */ 
Tine.Crm.settingsBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Crm',
    modelName: 'Settings',
    recordClass: Tine.Crm.Model.Settings
});
