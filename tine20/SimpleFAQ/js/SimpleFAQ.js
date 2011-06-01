/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine', 'Tine.SimpleFAQ');

/**
 * @namespace   Tine.SimpleFAQ
 * @class       Tine.SimpleFAQ.Application
 * @extends     Tine.Tinebase.Application
 */
Tine.SimpleFAQ.Application = Ext.extend(Tine.Tinebase.Application, {
    getTitle: function() {
        return this.i18n.gettext('FAQ');
    }
})

/**
 * @namespace   Tine.SimpleFAQ
 * @class       Tine.SimpleFAQ.MainScreen
 * @extends     Tine.widgets.MainScreen
 *
 * The mainscreen of the SimpleFAQ App
 */
Tine.SimpleFAQ.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Faq'
});

Tine.SimpleFAQ.TreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'SimpleFAQTreePanel';
    this.recordClass = Tine.SimpleFAQ.Model.Faq;

    this.filterMode = 'filterToolbar';
    Tine.SimpleFAQ.TreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.SimpleFAQ.TreePanel , Tine.widgets.container.TreePanel);

Tine.SimpleFAQ.FilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.SimpleFAQ.FilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.SimpleFAQ.FilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'SimpleFAQ_Model_FaqFilter'}]
});

/**
 * @namespace Tine.SimpleFAQ
 * @class Tine.SimpleFAQ.faqBackend
 * @extends Tine.Tinebase.data.RecordProxy
 *
 * Faq Backend
 */
Tine.SimpleFAQ.faqBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'SimpleFAQ',
    modelName: 'Faq',
    recordClass: Tine.SimpleFAQ.Model.Faq
});

/**
 * @namespace Tine.SimpleFAQ
 * @class Tine.SimpleFAQ.settingsBackend
 * @extends Tine.Tinebase.data.RecordProxy
 *
 * Settings Backend
 */
Tine.SimpleFAQ.settingsBackend = new Tine.Tinebase.data.RecordProxy({
   appName: 'SimpleFAQ' ,
   modelName: 'Settings',
   recordClass: Tine.SimpleFAQ.Model.Settings
});