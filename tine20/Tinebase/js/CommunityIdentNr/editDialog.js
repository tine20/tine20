Ext.ns('Tine.Tinebase');

Tine.Tinebase.CommunityIdentNrEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'CommunityIdentNrEditWindow_',
    appName: 'Tinebase',
    modelName: 'CommunityIdentNr',


    windowHeight: 600,
    evalGrants: false,

    /**
     * inits the component
     */
    initComponent: function () {
        this.recordClass = Tine.Tinebase.Model.CommunityIdentNr;
        Tine.Tinebase.CommunityIdentNrEditDialog.superclass.initComponent.call(this);
    },

    /**
     * executed after record got updated from proxy, if json Data is given, it's used
     *
     * @private
     */
    
    onAfterRecordLoad: function (jsonData) {
        Tine.Tinebase.CommunityIdentNrEditDialog.superclass.onAfterRecordLoad.call(this);
        //It should not be possible to edit COmmunity Ident Nrs via Edit Dialog
        this.btnSaveAndClose.hide(true);
    }
});