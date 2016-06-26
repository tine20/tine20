/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * Bbar Grid Panel
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Bbar Grid Panel</p>
 * <p><pre>
 *     Grid panel with bottom toolbar for add/edit/delete buttons
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.BbarGridPanel
 */
Tine.widgets.grid.BbarGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    editDialogRecordProperty: null,
    editDialog: null,
    storeRemoteSort: false,
    usePagingToolbar: false,
    
    /**
     * inits this cmp
     * 
     * @private
     */
    initComponent: function() {
        this.bbar = [];
        
        this.i18nEmptyText = this.i18nEmptyText ||
            String.format(i18n._(
                "There could not be found any {0}. Please try to change your filter-criteria or view-options."),
                this.i18nRecordsName
            );

        Tine.widgets.grid.BbarGridPanel.superclass.initComponent.call(this);
        
        this.fillBottomToolbar();
        
        this.store.on('add', this.updateTitle, this);
        this.store.on('remove', this.updateTitle, this);

        // update count of tab
        if (this.editDialogRecordProperty) {
            (function () {
                this.updateTitle((
                    this.editDialog.record.data.hasOwnProperty(this.editDialogRecordProperty)
                        && this.editDialog.record.data[this.editDialogRecordProperty])
                    ? this.editDialog.record.data[this.editDialogRecordProperty].length
                    : 0
                );
            }).defer(100, this);
        }
    },

    /**
     * overwrites the default function
     */
    initFilterPanel: function() {},
    
    /**
     * updates the title ot the tab
     * 
     * @param {Number} count
     */
    updateTitle: function(count) {
        count = Ext.isNumber(count) ? count : this.store.getCount();
        this.setTitle((count > 1 ? this.i18nRecordsName : this.i18nRecordName) + ' (' + count + ')');
    },
    
    /**
     * will be called in Edit Dialog Mode
     */
    fillBottomToolbar: function() {
        var tbar = this.getBottomToolbar();
        tbar.addButton(new Ext.Button(this.action_editInNewWindow));
        tbar.addButton(new Ext.Button(this.action_addInNewWindow));
        tbar.addButton(new Ext.Button(this.action_deleteRecord));
    }
});
