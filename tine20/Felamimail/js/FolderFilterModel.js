/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * TODO extend Tine.widgets.grid.FilterModelMultiSelect
 */
Ext.ns('Tine.Felamimail');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.Felamimail.FolderFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * TODO         use path
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */
Tine.Felamimail.FolderFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    
    /**
     * @cfg {Array} operators allowed operators
     */
    operators: ['in'],
    
    /**
     * @cfg {String} field
     */
    field: 'folder_id',
    
    /**
     * @cfg {String} defaultOperator default operator, one of <tt>{@link #operators} (defaults to equals)
     */
    defaultOperator: 'equals',
    
    /**
     * @cfg {String} defaultValue default value (defaults to all)
     */
    //defaultValue: {path: '/'},
    
    /**
     * @private
     */
    initComponent: function() {
        this.operators = ['in'];
        this.label = this.app.i18n._('Folder');

        Tine.Felamimail.FolderFilterModel.superclass.initComponent.call(this);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        
        var value = new Tine.Felamimail.FolderFilterModelValueField({
            app: this.app,
            filter: filter,
            width: 200,
            id: 'tw-ftb-frow-valuefield-' + filter.id,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        value.on('select', this.onFiltertrigger, this);
        return value;
        
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.felamimail.folder.filtermodel'] = Tine.Felamimail.FolderFilterModel;

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.FolderFilterModelValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * TODO         add grid + folder/account selection
 * TODO         make it work
 */
Tine.Felamimail.FolderFilterModelValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,
    layerAlign : 'tr-br?',
    minLayerWidth: 400,
    layerHeight: 300,
    
    lazyInit: true,
    
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    
    initComponent: function() {
        this.fakeRecord = new Tine.Felamimail.Model.Folder(Tine.Felamimail.Model.Folder.getDefaultData());
        
        //this.on('beforecollapse', this.onBeforeCollapse, this);
        
        this.supr().initComponent.call(this);
    },
    
    getFormValue: function() {
        //this.attendeeGridPanel.onRecordUpdate(this.fakeRecord);
        return this.fakeRecord.get('globalname');
    },
    
    getItems: function() {
        
        this.attendeeGridPanel = new Tine.Calendar.AttendeeGridPanel({
            title: this.app.i18n._('Select Folders'),
            height: this.layerHeight || 'auto',
            showNamesOnly: true
        });
        var items = [this.attendeeGridPanel];
        
        return items;
    },
    
    /**
     * cancel collapse if ctx menu is shown
     */
//    onBeforeCollapse: function() {
//        
//        return (!this.attendeeGridPanel.ctxMenu || this.attendeeGridPanel.ctxMenu.hidden) &&
//                !this.attendeeGridPanel.editing;
//    },
    
    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];
        this.fakeRecord.set('folder_id', '');
        this.fakeRecord.set('folder_id', value);
        this.currentValue = [];
        
        var folderStore = this.app.getFolderStore();
        
        var a = [], folder;
        for (var i=0; i < value.length; i++) {
            folder = folderStore.getById(value[i]);
            this.currentValue.push(folder.id);
            a.push(folder.get('globalname'));
        }
        
        this.setRawValue(a.join(', '));
        return this;
        
    }
    
    /**
     * sets values to innerForm
     */
//    setFormValue: function(value) {
//        this.attendeeGridPanel.onRecordLoad(this.fakeRecord);
//    }
});

