/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListMemberGridPanel
 * @extends     Ext.grid.EditorGridPanel
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 */
Tine.Addressbook.ListMemberGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    clicksToEdit: 1,

    /**
     * init component
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Addressbook');
        
        this.title = this.hasOwnProperty('title') ? this.title : this.app.i18n._('Members');
        this.plugins = this.plugins || [];

        this.sm = new Ext.grid.RowSelectionModel({singleSelect:true});
        this.sm.on('selectionchange', function(sm){
            this.removeBtn.setDisabled(sm.getCount() < 1);
        }, this);

        this.tbar = [{
            text: this.app.i18n._('Add'),
            handler: function(){
                this.stopEditing();
                this.store.insert(0, new Tine.Addressbook.Model.Contact({id: ""}));
                this.getView().refresh();
                this.getSelectionModel().selectRow(0);
                this.startEditing(0, 0);
            }.createDelegate(this)
        },{
            ref: '../removeBtn',
            text: this.app.i18n._('Remove'),
            disabled: true,
            handler: function(){
                this.stopEditing();
                var s = this.getSelectionModel().getSelections();
                for(var i = 0, r; r = s[i]; i++){
                    this.store.remove(r);
                }
            }.createDelegate(this)
        }]

        this.initColumns();
        this.store = this.store = new Ext.data.Store({
            autoSave: false,
            fields:  Tine.Addressbook.Model.Contact,
            proxy: Tine.Addressbook.contactBackend,
            reader: Tine.Addressbook.contactBackend.getReader(),
        });

        this.addListener("afteredit", this._afterEdit, this);

        Tine.Addressbook.ListMemberGridPanel.superclass.initComponent.call(this);
    },

    /**
     * initialises grid with an array of member uids
     */
    setMembers: function(members) {
        if (members) {
            var options = {params: {filter: [ { "field":"id","operator":"in", "value": members } ]}};
            this.store.load(options);
            this.store.sort("n_fileas");
        }
    },

    /**
     * returns current array of member uids
     */
    getMembers: function() {
        var result = [];
       for (var i = 0; i < this.store.getCount(); i++){
            var item = this.store.getAt(i).data;
            if (item.id != "") {
                result.push(item.id);
            }
        } 
        return result;
    },

    /**
     * init columns
     */
    initColumns: function() {
        this.editor = new Tine.Addressbook.SearchCombo({});
        this.columns = [
        {
            id: 'n_fileas',
            dataIndex: 'n_fileas',
            width: 200,
            sortable: true,
            header: this.app.i18n._('Name'),
            editor: this.editor
        },
        {
            id: 'email',
            dataIndex: 'email',
            width: 300,
            sortable: true,
            header: this.app.i18n._('Email'),
            editor: this.editor
        }];
    },

    /**
     * afteredit Event Handler
     */
    _afterEdit: function(e) {
        this.store.removeAt(e.row);
        this.store.insert(e.row, this.editor.selectedRecord);
    }

});
