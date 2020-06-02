/*
 * Tine 2.0
 * Filemanager combo box and store
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Filemanager');

/**
 * Node selection combo box
 * 
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.SearchCombo
 * @extends     Ext.form.TriggerField
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Filemanager.SearchCombo
 */
Tine.Filemanager.SearchCombo = Ext.extend(Ext.form.TriggerField, {
    
    allowBlank: false,
    singleSelect: true,
    constraint: null,

    itemSelector: 'div.search-item',
    minListWidth: 200,

    app: null,
    
    recordClass: null,
    recordProxy: null,
    
    initComponent: function(){
        this.recordClass = Tine.Filemanager.Model.Node;
        this.recordProxy = Tine.Filemanager.fileRecordBackend;

        if (null === this.app) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
        }

        this.supr().initComponent.call(this);

        this.addEvents(
            /**
             * @param selected node
             */
            'select'
        );
    },
    
    onTriggerClick: function () {
        var filepicker = new Tine.Filemanager.FilePickerDialog({
            windowTitle: this.title,
            singleSelect: this.singleSelect,
            constraint: this.constraint
        });

        filepicker.on('selected', function (node) {
            if (!node || 0 === node.length) {
                return true;
            }

            this.fireEvent('select', node[0]);
            this.setValue(node[0].path);

        }, this);

        filepicker.openWindow();
    }
});

Tine.widgets.form.RecordPickerManager.register('Filemanager', 'Node', Tine.Filemanager.SearchCombo);
