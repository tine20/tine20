/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */
Ext.ns('Tine.Expressomail');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.Expressomail.FolderFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */
Tine.Expressomail.FolderFilterModel = Ext.extend(Tine.widgets.grid.PickerFilter, {

    /**
     * @cfg 
     */
    operators: ['in', 'notin'],
    field: 'path',
    
    /**
     * @private
     */
    initComponent: function() {
        this.label = this.app.i18n._('Folder');
        
        this.multiselectFieldConfig = {
            labelField: 'path',
            selectionWidget: new Tine.Expressomail.FolderSelectTriggerField({
                allAccounts: true
            }),
            recordClass: Tine.Expressomail.Model.Folder,
            valueStore: this.app.getFolderStore(),
            
            /**
             * functions
             */
            labelRenderer: Tine.Expressomail.GridPanel.prototype.accountAndFolderRenderer.createDelegate(this),
            initSelectionWidget: function() {
                this.selectionWidget.onSelectFolder = this.addRecord.createDelegate(this);
            },
            isSelectionVisible: function() {
                return this.selectionWidget.selectPanel && ! this.selectionWidget.selectPanel.isDestroyed        
            },
            getRecordText: function(value) {
                var path = (Ext.isString(value)) ? value : (value.path) ? value.path : '/' + value.id,
                    index = this.valueStore.findExact('path', path),
                    record = this.valueStore.getAt(index),
                    text = null;
                
                if (! record) {
                    // try account
                    var accountId = path.substr(1, 40);
                    record = this.app.getAccountStore().getById(accountId);
                }
                if (record) {
                    this.currentValue.push(path);
                    // always copy/clone record because it can't exist in 2 different stores
                    this.store.add(record.copy());
                    text = this.labelRenderer(record.id, {}, record);
                } else {
                    text = path;
                    this.currentValue.push(path);
                }
                
                return text;
            }
        };

        Tine.Expressomail.FolderFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.expressomail.folder.filtermodel'] = Tine.Expressomail.FolderFilterModel;
