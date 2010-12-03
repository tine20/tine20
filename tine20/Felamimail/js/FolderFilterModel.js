/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */
Ext.ns('Tine.Felamimail');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.Felamimail.FolderFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */
Tine.Felamimail.FolderFilterModel = Ext.extend(Tine.widgets.grid.FilterModelMultiSelect, {

    /**
     * @cfg 
     * TODO make notin work
     */
    operators: ['in'/*, 'notin' */],
    field: 'folder_id',
    
    /**
     * @private
     */
    initComponent: function() {
        this.label = this.app.i18n._('Folder');
        
        this.multiselectFieldConfig = {
            xtype: 'wdgt.pickergrid',
            labelField: 'globalname',
            layerHeight: 200,
            selectionWidget: new Tine.Felamimail.FolderSelectTriggerField({
                allAccounts: true
            }),
            recordClass: Tine.Felamimail.Model.Folder,
            valueStore: this.app.getFolderStore(),
            
            /**
             * functions
             */
            initSelectionWidget: function() {
                this.selectionWidget.onSelectFolder = this.addRecord.createDelegate(this);
            },
            isSelectionVisible: function() {
                return this.selectionWidget.selectPanel && ! this.selectionWidget.selectPanel.isDestroyed        
            }
        };

        Tine.Felamimail.FolderFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.felamimail.folder.filtermodel'] = Tine.Felamimail.FolderFilterModel;
