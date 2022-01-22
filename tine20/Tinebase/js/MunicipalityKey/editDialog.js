Ext.ns('Tine.Tinebase');

Tine.Tinebase.MunicipalityKeyEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'MunicipalityKeyEditWindow_',
    appName: 'Tinebase',
    modelName: 'MunicipalityKey',


    windowHeight: 600,
    evalGrants: false,

    /**
     * inits the component
     */
    initComponent: function () {
        this.recordClass = Tine.Tinebase.Model.MunicipalityKey;
        Tine.Tinebase.MunicipalityKeyEditDialog.superclass.initComponent.call(this);
    },

    /**
     * executed after record got updated from proxy, if json Data is given, it's used
     *
     * @private
     */
    
    onAfterRecordLoad: function (jsonData) {
        Tine.Tinebase.MunicipalityKeyEditDialog.superclass.onAfterRecordLoad.call(this);
        //It should not be possible to edit COmmunity Ident Nrs via Edit Dialog
        this.btnSaveAndClose.hide(true);
    }
});
