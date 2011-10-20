/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.dialog');

/**
 * Generic 'Import' dialog
 *
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.ImportDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @constructor
 * @param       {Object} config The configuration options.
 * 
 * TODO add app grid to show results when dry run is selected
 */
Tine.widgets.dialog.ImportDialog = Ext.extend(Tine.widgets.dialog.WizardPanel, {
    /**
     * @cfg {String} appName (required)
     */
    appName: null,
    
    /**
     * @cfg {String} modelName (required)
     */
    modelName: null, 
    
    /**
     * @cfg {String} defaultImportContainer
     */
    defaultImportContainer: null,
    
    /**
     * @property recordClass
     * @type Tine.Tinebase.data.Record
     */
    recordClass: null,
    
    /**
     * @property definitionsStore
     * @type Ext.data.JsonStore
     */
    definitionsStore: null,
    
    /**
     * @property exceptionStore
     * @type Ext.data.JsonStore
     */
    exceptionStore: null,
    
    // private config overrides
    windowNamePrefix: 'ImportWindow_',
    
    /**
     * init import wizard
     */
    initComponent: function() {
        try {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
            this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.appName, this.modelName);
            
            // init definitions
            this.definitionsStore = new Ext.data.JsonStore({
                fields: Tine.Tinebase.Model.ImportExportDefinition,
                root: 'results',
                totalProperty: 'totalcount',
                remoteSort: false
            });
            if (Tine[this.appName].registry.get('importDefinitions')) {
                this.definitionsStore.loadData(Tine[this.appName].registry.get('importDefinitions'));
            }
            
            // init exception store
            this.exceptionStore = new Ext.data.JsonStore({
                mode: 'local',
                idProperty: 'record_idx',
                fields: ['record_idx', 'code', 'exception', 'resolveStrategy']
            });
        
            this.items = [
                this.getFilePanel(),
                this.getOptionsPanel(),
                this.getConflictsPanel(),
                this.getSummaryPanel()
            ];
            
            Tine.widgets.dialog.ImportDialog.superclass.initComponent.call(this);
        } catch (e) {
            Tine.log.err('Tine.widgets.dialog.ImportDialog::initComponent');
            Tine.log.err(e.stack ? e.stack : e);
        }
    },
    
    /**
     * do import request
     * 
     * @param {Function} callback
     * @param {Object}   importOptions
     */
    doImport: function(callback, importOptions) {
        try {
            Ext.Ajax.request({
                scope: this,
                timeout: 1800000, // 30 minutes
                callback: this.onImportResponse.createDelegate(this, [callback], true),
                params: {
                    method: this.appName + '.import' + this.recordClass.getMeta('recordsName'),
                    tempFileId: this.uploadButton.getTempFileId(),
                    definitionId: this.definitionCombo.getValue(),
                    importOptions: Ext.apply({
                        container_id: this.containerCombo.getValue(),
                    }, importOptions || {})
                }
            });
            
        } catch (e) {
            Tine.log.err('Tine.widgets.dialog.ImportDialog::doImport');
            Tine.log.err(e.stack ? e.stack : e);
        }
        
    },
    
    /**
     * called when import request sends response
     * 
     * @param {Object}   request
     * @param {Boolean}  success
     * @param {Object}   response
     * @param {Function} callback
     */
    onImportResponse: function(request, success, response, callback) {
        try {
            response = Ext.util.JSON.decode(response.responseText);
            
            // load exception store
            this.exceptionStore.loadData(response.exceptions);
            this.exceptionStore.filterBy(this.exceptionStoreFilter, this);
            
            // update conflict panel
//            var duplicatecount = response.duplicatecount || 0,
//                recordsName = this.app.i18n.n_(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), duplicatecount);
//                
//            this.conflictsLabel.setText(String.format(this.conflictsLabel.rawText, duplicatecount, recordsName), false);
            if (this.exceptionStore.getCount()) {
                this.loadConflict(0);
            }
            
            // finlay apply callback
            if (Ext.isFunction(callback)) {
                callback.call(this, request, success, response);
            }
            
        } catch (e) {
            Tine.log.err('Tine.widgets.dialog.ImportDialog::onImportResponse');
            Tine.log.err(e.stack ? e.stack : e);
        }
    },
    
    exceptionStoreFilter: function(record, id) {
        return record.get('code') == 629 && ! record.get('resolveStrategy');
    },
    
    /********************************************************** FILE PANEL **********************************************************/
    
    /**
     * returns the file panel of this wizard (step 1)
     */
    getFilePanel: function() {
        if (this.filePanel) {
            return this.filePanel;
        }
        
        return {
            title: _('Choose File and Format'),
            layout: 'fit',
            border: false,
            xtype: 'form',
            frame: true,
            ref: '../filePanel',
            items: [{
                xtype: 'label',
                html: '<p>' + String.format(_('Please choose the file that contains the {0} you want to add to Tine 2.0'), this.recordClass.getRecordsName()).replace(/Tine 2\.0/g, Tine.title) + '</p>'
            }, {
                xtype: 'tw.uploadbutton',
                ref: '../uploadButton',
                text: String.format(_('Select file containing your {0}'), this.recordClass.getRecordsName()),
                handler: this.onFileReady,
                // @TODO!!! get this dynamically
                allowedTypes: ['csv', 'odt'],
                scope: this
            }, {
                xtype: 'label',
                cls: 'tb-login-big-label',
                html: _('What should the file you upload look like?') + '<br />'
            }, {
                xtype: 'label',
                html: '<p>' + _('Tine 2.0 does not understand all kind of files you might want to upload. You will have to manually adjust your file so Tine 2.0 can handle it.').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
            }, {
                xtype: 'label',
                html: '<p>' + _('Following you find a list of all supported import formats and a sample file, how Tine 2.0 expects your file to look like.').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
            }, {
                xtype: 'label',
                html: '<p>' + _('Please select the import format of the file you want to upload').replace(/Tine 2\.0/g, Tine.title) + '</p>'
            }, {
                xtype: 'combo',
                ref: '../definitionCombo',
                store: this.definitionsStore,
                displayField:'name',
                valueField:'id',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                allowBlank: false,
                forceSelection: true,
                value: Tine.Addressbook.registry.get('defaultImportDefinition').id,
                listeners: {
                    scope: this,
                    'select': function(combo, record, index) {
                        this.definitionDescription.setValue(record.get('description'));
                        this.manageButtons();
                    }
                }
            }, {
                xtype: 'displayfield',
                fieldLabel: _('Import description'),
                ref: '../definitionDescription',
                height: 70,
                value: this.definitionsStore.getById(Tine.Addressbook.registry.get('defaultImportDefinition').id).get('description'),
                style: {
                    border: 'silver 1px solid',
                    padding: '3px',
                    height: '11px'
                }
            }],
            nextIsAllowed: (function() {
                return this.definitionCombo && this.definitionCombo.getValue() && this.uploadButton && this.uploadButton.fileRecord;
            }).createDelegate(this)
        };
    },
    
    onFileReady: function() {
//        console.log(arguments);
        this.manageButtons();
    },
    
    /********************************************************** OPTIONS PANEL **********************************************************/
    getOptionsPanel: function() {
        if (this.optionsPanel) {
            return this.optionsPanel;
        }
        
        return {
            title: _('Set Import Options'),
            layout: 'fit',
            border: false,
            xtype: 'form',
            frame: true,
            ref: '../optionsPanel',
            items: [{
                xtype: 'label',
                html: '<p>' + String.format(_('Select {0} to add you {1} to:'), this.recordClass.getContainerName(), this.recordClass.getRecordsName()) + '</p>'
            }, new Tine.widgets.container.selectionComboBox({
                id: this.app.appName + 'EditDialogContainerSelector',
                width: 300,
                ref: '../containerCombo',
                stateful: false,
                containerName: this.recordClass.getContainerName(),
                containersName: this.recordClass.getContainersName(),
                appName: this.appName,
                value: this.defaultImportContainer,
                requiredGrant: false
            }), new Tine.widgets.tags.TagPanel({
                app: this.appName,
                border: true,
                collapsible: false,
                height: 200
            })],
            
            /**
             * check if next button is allowed
             */
            nextIsAllowed: (function() {
                return this.containerCombo && this.containerCombo.getValue();
            }).createDelegate(this),
            
            /**
             * next button handler for this panel
             */
            onNextButton: (function() {
                if (! this.checkMask) {
                    this.checkMask = new Ext.LoadMask(this.getEl(), {msg: _('Checking Import')});
                }
                
                this.checkMask.show();
            
                this.doImport(function(request, success, response) {
                    this.checkMask.hide();
                    
                    // jump to finish panel if no conflicts where detected
                    if (! response.duplicatecount) {
                        this.navigate(+2);
                    } else {
                        this.navigate(+1);
                    }
                }, {dryrun: true});
                
            }).createDelegate(this)
        }
    },
    
    
    /********************************************************** CONFLICT PANEL **********************************************************/
    
    getConflictsPanel: function() {
        if (this.conflictsPanel) {
            return this.conflictsPanel;
        }
        
        return {
            title: _('Resolve Conflicts'),
            layout: 'vbox',
            border: false,
            xtype: 'form',
            frame: true,
            ref: '../conflictsPanel',
            items: [/*{
                xtype: 'label',
                ref: '../conflictsLabel',
                rawText: '<p>' + _('There are {0} {1} that might already exist.') + '</p>',
                html: '<p></p>',
                height: 20
            },*/ {
                xtype: 'paging',
                ref: '../conflictPagingToolbar',
                pageSize: 1,
                beforePageText: _('Conflict'),
                firstText : _('First Conflict'),
                prevText : _('Previous Conflict'),
                nextText : _('Next Conflict'),
                lastText : _('Last Conflict'),
                store: this.exceptionStore,
                doLoad: this.loadConflict.createDelegate(this),
                onLoad: Ext.emptyFn,
                listeners: {afterrender: function(t){t.refresh.hide()}},
                items: ['->', {
                    text: _('Conflict is resolved'),
                    scope: this,
                    handler: this.onResolveConflict
                }]
            }, new Tine.widgets.dialog.DuplicateResolveGridPanel({
                flex: 1,
                ref: '../duplicateResolveGridPanel',
                header: false,
                app: this.app,
                store: new Tine.widgets.dialog.DuplicateResolveStore({
                    app: this.app,
                    recordClass: this.recordClass
                })
            })],
            
            /**
             * check if next button is allowed
             */
            nextIsAllowed: (function() {
                var nextIsAllowed = true;
                
                // check if all conflicts are resolved
                this.exceptionStore.each(function(exception) {
                    if (! exception.get('resolveStrategy')) {
                        nextIsAllowed = false;
                        return false;
                    }
                }, this);
                
                return nextIsAllowed;
                
            }).createDelegate(this)
            
            
//            listeners: {
//                scope: this,
//                show: function() {
//                    console.log('SHOW CONFLICT PANEL');
//                    console.log(this.lastImportResponse);
//                    
//                    // load conflicts
//                    var duplicatecount = this.lastImportResponse ? this.lastImportResponse.duplicatecount : 0,
//                        recordsName = this.app.i18n.n_(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), duplicatecount);
//                        
//                    this.conflictsLabel.setText(String.format(this.conflictsLabel.rawText, duplicatecount, recordsName), false);
//                }
//            }
            
            
        }
    },
    
    onResolveConflict: function() {
        var index = this.conflictPagingToolbar.cursor,
            record = this.exceptionStore.getAt(index),
            strategy = this.duplicateResolveGridPanel.getStore().resolveStrategy;
        
        // mark exception record resolved
        record.set('resolveStrategy', strategy);
        
        // load next conflict
        this.exceptionStore.filterBy(this.exceptionStoreFilter, this);
        this.manageButtons();
        
        this.loadConflict(this.exceptionStore.getCount() > index ? index : index-1);
    },
    
    /**
     * load conflict with given index
     * 
     * @TODO: exception might be no duplicate!!! -> use exception panel?
     * @TODO: check index / load other if not exist (-1) / show info if none or empty
     * 
     * @param {Number} index
     */
    loadConflict: function(index) {
        if (! this.conflictMask) {
            this.conflictMask = new Ext.LoadMask(this.getEl(), {msg: _('Processing Conflict Data'), hidden: true});
        }

        // give DOM the time to show loadMask
        if (this.conflictMask.hidden) {
            this.conflictMask.show();
            this.conflictMask.hidden = false;
            
            return this.loadConflict.defer(10, this, arguments);
        }
        
        try {
            
            var record = this.exceptionStore.getAt(index);
            
            if (record) {
                this.duplicateResolveGridPanel.getStore().loadData(record.get('exception'));
            } else {
                this.duplicateResolveGridPanel.getStore().removeAll();
                this.duplicateResolveGridPanel.getView().mainBody.update('<br />  ' + _('No conflict to resolve'));
//                this.navigate(+1);
            }
            
            
            // update paging toolbar
            var p = this.conflictPagingToolbar,
                ap = index+1,
                ps = this.exceptionStore.getCount();
            
            p.cursor = index;
            p.afterTextItem.setText(String.format(p.afterPageText, ps));
            p.inputItem.setValue(ap);
            p.first.setDisabled(ap == 1);
            p.prev.setDisabled(ap == 1);
            p.next.setDisabled(ap == ps);
            p.last.setDisabled(ap == ps);
            p.refresh.enable();
            p.updateInfo();
            
            this.conflictMask.hide();
            this.conflictMask.hidden = true;
        } catch (e) {
            Tine.log.err('Tine.widgets.dialog.ImportDialog::loadConflict');
            Tine.log.err(e.stack ? e.stack : e);
        }
    },
    
    /********************************************************** SUMMARY PANEL **********************************************************/
    
    getSummaryPanel: function() {
        if (this.summaryPanel) {
            return this.summaryPanel;
        }
        
        return {
            title: _('Summary'),
            layout: 'fit',
            border: false,
            xtype: 'form',
            frame: true,
            ref: '../summaryPanel',
            items: [{}],
            
            /**
             * finish button handler for this panel
             */
            onFinishButton: (function() {
                if (! this.importMask) {
                    this.importMask = new Ext.LoadMask(this.getEl(), {msg: String.format(_('Importing {0}'), this.recordClass.getRecordsName())});
                }
                
                this.importMask.show();
            
                this.doImport(function(request, success, response) {
                    this.importMask.hide();
                    
                    this.fireEvent('finish', this, this.layout.activeItem);
                });
                
            }).createDelegate(this)
            
        }
    }
});

/**
 * credentials dialog popup / window
 */
Tine.widgets.dialog.ImportDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        name: Tine.widgets.dialog.ImportDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.ImportDialog',
        contentPanelConstructorConfig: config//,
//        modal: true
    });
    return window;
};
