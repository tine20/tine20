/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Martin Jatho <m.jatho@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

require('./nodeActions');

Tine.Filemanager.nodeContextMenu = {
    actionMgr: Tine.Filemanager.nodeActionsMgr,

    /**
     * on pause
     * @param {} button
     * @param {} event
     */
    onPause: function (button, event) {

        var grid = this.scope;
        var gridStore = grid.store;
        gridStore.suspendEvents();
        var selectedRows = grid.selectionModel.getSelections();
        for(var i=0; i < selectedRows.length; i++) {
            var fileRecord = selectedRows[i];
            if(fileRecord.fileRecord) {
                fileRecord = fileRecord.fileRecord;
            }
            var upload = Tine.Tinebase.uploadManager.getUpload(fileRecord.get('uploadKey'));
            if (upload) {
                upload.setPaused(true);
            }
        }
        gridStore.resumeEvents();
        grid.actionUpdater.updateActions(gridStore);
        this.scope.selectionModel.deselectRange(0, this.scope.selectionModel.getCount());
    },

    
    /**
     * on resume
     * @param {} button
     * @param {} event
     */
    onResume: function (button, event) {
        
        var grid = this.scope;
        var gridStore = grid.store;
        gridStore.suspendEvents();
        var selectedRows = grid.selectionModel.getSelections();
        for(var i=0; i < selectedRows.length; i++) {
            var fileRecord = selectedRows[i];
            if(fileRecord.fileRecord) {
                fileRecord = fileRecord.fileRecord;
            }
            var upload = Tine.Tinebase.uploadManager.getUpload(fileRecord.get('uploadKey'));
            upload.resumeUpload();
        }
        gridStore.resumeEvents();
        grid.actionUpdater.updateActions(gridStore);
        this.scope.selectionModel.deselectRange(0, this.scope.selectionModel.getCount());

    },
    
    /**
     * checks whether resume button shuold be enabled or disabled
     * 
     * @param action
     * @param grants
     * @param records
     */
    isResumeEnabled: function(action, grants, records) {
        
        for(var i=0; i<records.length; i++) {
            
            var record = records[i];
            if(record.fileRecord) {
                record = record.fileRecord;
            }
            
            if(record.get('type') == 'folder') {
                action.hide();
                return;
            }
        }
       
        for(var i=0; i < records.length; i++) {
            
            var record = records[i];
            if(record.fileRecord) {
                record = record.fileRecord;
            }
            if(!record.get('status') || (record.get('type') != 'folder' &&  record.get('status') != 'uploading' 
                    &&  record.get('status') != 'paused' && record.get('status') != 'pending')) {
                action.hide();
                return;
            }
        }
        
        action.show();
        
        for(var i=0; i < records.length; i++) {
            
            var record = records[i];
            if(record.fileRecord) {
                record = record.fileRecord;
            }
            
            if(record.get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if(record.get('status') && record.get('status') != 'paused') {
                action.setDisabled(true);
            }
            
        }   
    },
    
    /**
     * checks whether pause button shuold be enabled or disabled
     * 
     * @param action
     * @param grants
     * @param records
     */
    isPauseEnabled: function(action, grants, records) {
        
        for(var i=0; i<records.length; i++) {
            
            var record = records[i];
            if(record.fileRecord) {
                record = record.fileRecord;
            }
            
            if(record.get('type') === 'folder') {
                action.hide();
                return;
            }
        }
        
        for(var i=0; i < records.length; i++) {
            
            var record = records[i];
            if(record.fileRecord) {
                record = record.fileRecord;
            }
            
            if(!record.get('status') || (record.get('type ') != 'folder' && record.get('status') != 'paused'
                    &&  record.get('status') != 'uploading' && record.get('status') != 'pending')) {
                action.hide();
                return;
            }
        }
        
        action.show();
        
        for(var i=0; i < records.length; i++) {
            
            var record = records[i];
            if(record.fileRecord) {
                record = record.fileRecord;
            }
            
            if(record.get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if(record.get('status') && record.get('status') !=='uploading'){
                action.setDisabled(true);
            }
            
        }
    }
};

// extends Tine.widgets.tree.ContextMenu
Ext.applyIf(Tine.Filemanager.nodeContextMenu, Tine.widgets.tree.ContextMenu);
