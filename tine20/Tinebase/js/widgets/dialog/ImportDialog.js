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
     * @property allowedFileExtensions
     * @type Array
     */
    allowedFileExtensions: null,
    
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
     * @property selectedDefinition
     * @type Ext.data.Record
     */
    selectedDefinition: null,
    
    /**
     * @property exceptionStore
     * @type Ext.data.JsonStore
     */
    exceptionStore: null,
    
    /**
     * @property lastImportResponse
     * @type Objcet resutls of the last import request
     */
    lastImportResponse: null,
    
    // private config overrides
    windowNamePrefix: 'ImportWindow_',
    
    /**
     * init import wizard
     */
    initComponent: function() {

        Tine.log.debug('Tine.widgets.dialog.ImportDialog::initComponent this');
        Tine.log.debug(this);

        this.app = Tine.Tinebase.appMgr.get(this.appName);
        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.appName, this.modelName);

        this.allowedFileExtensions = [];

        // init definitions
        this.definitionsStore = new Ext.data.JsonStore({
            fields: Tine.Tinebase.Model.ImportExportDefinition,
            root: 'results',
            totalProperty: 'totalcount',
            remoteSort: false
        });

        if (Tine[this.appName].registry.get('importDefinitions')) {
            Ext.each(Tine[this.appName].registry.get('importDefinitions').results, function(defData) {
                var options = defData.plugin_options,
                    extension = options ? options.extension : null;
                
                defData.label = this.app.i18n._hidden(options && options.label ? options.label : defData.name);
                
                if (this.allowedFileExtensions.indexOf(extension) == -1) {
                    this.allowedFileExtensions = this.allowedFileExtensions.concat(extension);
                }
                
                this.definitionsStore.addSorted(new Tine.Tinebase.Model.ImportExportDefinition(defData, defData.id));
            }, this);
            this.definitionsStore.sort('label');
        }
        if (! this.selectedDefinition && Tine[this.appName].registry.get('defaultImportDefinition')) {
            this.selectedDefinition = this.definitionsStore.getById(Tine[this.appName].registry.get('defaultImportDefinition').id);
        }

        // init exception store
        this.exceptionStore = new Ext.data.JsonStore({
            mode: 'local',
            idProperty: 'index',
            fields: ['index', 'code', 'message', 'exception', 'resolveStrategy', 'resolvedRecord', 'isResolved']
        });

        this.items = this.getItems();

        Tine.widgets.dialog.ImportDialog.superclass.initComponent.call(this);
    },

    getItems: function() {
        return [
            this.getFilePanel(),
            this.getOptionsPanel(),
            this.getConflictsPanel(),
            this.getSummaryPanel()
        ];
    },
    
    /**
     * close window on cancel
     */
    onCancelButton: function() {
        this.fireEvent('cancel', this, this.layout.activeItem);
        this.window.close();
    },

    /**
     * do import request
     * 
     * @param {Function} callback
     * @param {Object}   importOptions
     */
    doImport: function(callback, importOptions, clientRecordData) {
        Ext.Ajax.request({
            scope: this,
            timeout: 1800000, // 30 minutes
            callback: this.onImportResponse.createDelegate(this, [callback], true),
            params: {
                method: this.appName + '.import' + this.recordClass.getMeta('modelName')  + 's',
                tempFileId: this.uploadButton.getTempFileId(),
                definitionId: this.definitionCombo.getValue(),
                importOptions: Ext.apply({
                    container_id: this.containerCombo.getValue(),
                    autotags: this.tagsPanel.getFormField().getValue()
                }, importOptions || {}),
                clientRecordData: clientRecordData
            }
        });
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
        response = Ext.util.JSON.decode(response.responseText);
        
        Tine.log.debug('Tine.widgets.dialog.ImportDialog::onImportResponse server response');
        Tine.log.debug(response);
        
        this.lastImportResponse = response;
        
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
    },
    
    exceptionStoreFilter: function(record, id) {
        return record.get('code') == 629 && ! record.get('isResolved');
    },
    
    /********************************************************** FILE PANEL **********************************************************/
    
    /**
     * returns the file panel of this wizard (step 1)
     * 
     * @TODO restrict allowed extensions on definition selection OR
     *       restirct allowed definitions on file selection
     */
    getFilePanel: function() {
        if (this.filePanel) {
            return this.filePanel;
        }
        
        var def = this.selectedDefinition,
            description = def ? def.get('description') : '',
            options = def ? def.get('plugin_options') : null,
            example = options && options.example ? options.example : '';
            
        return {
            title: _('Choose File and Format'),
            layout: 'vbox',
            border: false,
            xtype: 'ux.displaypanel',
            frame: true,
            ref: '../filePanel',
            items: [{
                xtype: 'panel',
                baseCls: 'ux-subformpanel',
                title: _('Choose Import File'),
                height: 100,
                items: [{
                    xtype: 'label',
                    html: '<p>' + _('Please choose the file that contains the records you want to add to Tine 2.0').replace(/Tine 2\.0/g, Tine.title) + '</p><br />'
                }, {
                    xtype: 'tw.uploadbutton',
                    ref: '../../uploadButton',
                    text: String.format(_('Select file containing your {0}'), this.recordClass.getRecordsName()),
                    handler: this.onFileReady,
                    allowedTypes: this.allowedFileExtensions,
                    scope: this
                }]
            }, {
                xtype: 'panel',
                baseCls: 'ux-subformpanel',
                title: _('What should the file you upload look like?'),
                flex: 1,
                items: [/*{
                    xtype: 'label',
                    cls: 'tb-login-big-label',
                    html: _('What should the file you upload look like?') + '<br />'
                },*/ {
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
                    ref: '../../definitionCombo',
                    store: this.definitionsStore,
                    displayField:'label',
                    valueField:'id',
                    mode: 'local',
                    triggerAction: 'all',
                    editable: false,
                    allowBlank: false,
                    forceSelection: true,
                    width: 400,
                    value: this.selectedDefinition ? this.selectedDefinition.id : null,
                    listeners: {
                        scope: this,
                        'select': this.onDefinitionSelect
                    }
                }, {
                    xtype: 'label',
                    ref: '../../exampleLink',
                    html: example ? ('<p><a href="' + example + '">' + _('Download example file') + '</a></p>') : '<p>&nbsp;</p>'
                }, {
                    xtype: 'displayfield',
//                    fieldLabel: _('Import description'),
                    ref: '../../definitionDescription',
                    height: 70,
                    value: description,
                    cls: 'x-ux-display-background-border',
                    style: 'padding-left: 5px;'
                }]
            }],
            nextIsAllowed: (function() {
                return this.definitionCombo && this.definitionCombo.getValue() && this.uploadButton && this.uploadButton.fileRecord;
            }).createDelegate(this)
        };
    },
    
    onFileReady: function() {
        this.manageButtons();
    },
    
    /**
     * select handler of definition combo
     */
    onDefinitionSelect: function(combo, record, index) {
        var description = record.get('description'),
            options = record.get('plugin_options'),
            example = options && options.example ? options.example : '';
        
        this.selectedDefinition = record;
        
        this.definitionDescription.setValue(description);
        this.exampleLink.setText(example ? ('<p><a href="' + example + '">' + _('Download example file') + '</a></p>') : '<p>&nbsp;</p>', false);
        
        this.manageButtons();
    },
    
    /**
     * get options of the plugin from the currently selected definition
     */
    getImportPluginOptions: function() {
        var options = this.selectedDefinition ? this.selectedDefinition.get('plugin_options') : null;
            
        return options || {};
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
                listeners: {
                    scope: this,
                    select: function() {
                        this.manageButtons();
                    }
                },
                requiredGrant: false // 'add' ?
            }), new Tine.widgets.tags.TagPanel({
                app: this.appName,
                ref: '../tagsPanel',
                style: 'margin-top: 15px; border: 1px solid silver; border-top: none;',
                border: true,
                collapsible: false,
                height: 200
            })],
            
            listeners: {
                scope: this,
                show: function() {
                   var options = this.getImportPluginOptions();
                
                    if (options.autotags) {
                        var tags = options.autotags;
                        
                        Ext.each([].concat(tags), function(tag) {
                            tag.name = this.app.i18n._hidden(tag.name);
                            tag.description = this.app.i18n._hidden(tag.description);
                        }, this);
                        
                        this.tagsPanel.getFormField().setValue(tags);
                    }
                    
                    this.containerCombo.setValue(options.container_id ? options.container_id : this.defaultImportContainer);
                }
            },
            
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
                listeners: {afterrender: function(t){t.refresh.hide();}},
                items: [this.conflictIndexText = new Ext.Toolbar.TextItem({}), '->', {
                    text: _('Conflict is resolved'),
                    xtype: 'splitbutton',
                    scope: this,
                    handler: this.onResolveConflict,
                    menu: [{
                        text: _('Resolve all conflicts'),
                        scope: this,
                        handler: this.onResolveAllConflict
                    }]
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
            listeners: {
                scope: this,
                show: function() {
                    if (! this.exceptionStore.isFiltered()) {
                        this.exceptionStore.filterBy(this.exceptionStoreFilter, this);
                        this.manageButtons();
                    }
                }
            },
            /**
             * check if next button is allowed
             */
            nextIsAllowed: (function() {
                var nextIsAllowed = true;
                
                // check if all conflicts are resolved
                this.exceptionStore.each(function(exception) {
                    if (! exception.get('isResolved')) {
                        nextIsAllowed = false;
                        return false;
                    }
                }, this);
                
                return nextIsAllowed;
                
            }).createDelegate(this)
        };
    },
    
    /**
     * mark current conflict resolved
     */
    onResolveConflict: function() {
        var index = this.conflictPagingToolbar.cursor,
            record = this.exceptionStore.getAt(index),
            resolveStore = this.duplicateResolveGridPanel.getStore(),
            resolveStrategy = resolveStore.resolveStrategy,
            resolveRecord = resolveStore.getResolvedRecord();
        
        // mark exception record resolved
        record.set('resolveStrategy', resolveStrategy);
        record.set('resolvedRecord', resolveRecord);
        record.set('isResolved', true);
        
        // load next conflict
        this.exceptionStore.filterBy(this.exceptionStoreFilter, this);
        this.manageButtons();
        
        this.loadConflict(this.exceptionStore.getCount() > index ? index : index-1);
        
        if (Ext.isFunction(arguments[0])) {
            return arguments[0].call(this);
        }
    },
    
    /**
     * resolve all conflicts using current strategy
     */
    onResolveAllConflict: function() {
        
        // @TODO: disable grid to spedup things?
        if (this.exceptionStore.getCount() > 0) {
            this.conflictMask.show();
            this.conflictMask.hidden = false;
            
            return this.onResolveConflict.defer(20, this, [this.onResolveAllConflict]);
        } else {
            this.conflictMask.hide();
            this.conflictMask.hidden = true;
        }
    },
    
    /**
     * load conflict with given index
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
            
            return this.loadConflict.defer(200, this, arguments);
        }
            
        var thisRecord = this.exceptionStore.getAt(this.conflictPagingToolbar.cursor),
            nextRecord = this.exceptionStore.getAt(index),
            resolveStore = this.duplicateResolveGridPanel.getStore();
            
        // preserv changes
        if (this.conflictPagingToolbar.cursor != index && thisRecord && resolveStore.getCount()) {
            thisRecord.set('resolvedRecord', resolveStore.getResolvedRecord());
            thisRecord.set('resolveStrategy', resolveStore.resolveStrategy);
        }
        
        if (nextRecord) {
            resolveStore.loadData(nextRecord.get('exception'), nextRecord.get('resolveStrategy') || resolveStore.resolveStrategy, nextRecord.get('resolvedRecord'));
        } else {
            resolveStore.removeAll();
            this.duplicateResolveGridPanel.getView().mainBody.update('<br />  ' + _('No conflict to resolve'));
            // defer navigation to complete this step before
            this.navigate.defer(200, this, [+1]);
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
        this.conflictIndexText.setText(nextRecord ? 
            String.format(_('(This is record {0} in your import file)'), nextRecord.get('index') + 1) :
            _('No conflict to resolve')
        );
        
        this.conflictMask.hide();
        this.conflictMask.hidden = true;
    },
    
    /********************************************************** SUMMARY PANEL **********************************************************/
    
    getSummaryPanel: function() {
        if (this.summaryPanel) {
            return this.summaryPanel;
        }
        var exceptionExpander = new Ext.ux.grid.RowExpander({
            tpl : new Ext.XTemplate('{[this.showClientRecord(values)]}', { showClientRecord: function(values) {
                if (values && values.exception && values.exception.clientRecord) {
                    // there is no generic detailsPanel retirval yet
//                    if (Ext.isObject(values.exception.clientRecord)) {
//                        var detailsPanel = new Tine.Addressbook.ContactGridDetailsPanel({});
//                        
//                        return detailsPanel.tpl.apply(values.exception.clientRecord);
//                    }
                    return Ext.util.Format.htmlEncode(Ext.encode(values.exception.clientRecord));
                } else {
                    return _('No Detail Informations');
                }
            }})
        });
        return {
            title: _('Summary'),
            border: false,
            xtype: 'ux.displaypanel',
            frame: true,
            ref: '../summaryPanel',
            autoScroll: true,
            items: [{
                height: 100,
                ref: '../summaryPanelInfo',
                border: false,
                layout: 'ux.display',
                layoutConfig: {
                    background: 'border'
                }
            }, {
                ref: '../summaryPanelFailures',
                baseCls: 'ux-arrowcollapse',
                cls: 'ux-arrowcollapse-plain',
                collapsible: true,
                hidden: true,
                flex: 1,
                title:'',
                items: [{
                    xtype: 'grid',
                    store: this.exceptionStore,
                    autoHeight: true,
                    plugins: exceptionExpander,
                    columns: [
                        exceptionExpander,
                        { id: 'index', header: _('Index'), width: 60, sortable: false, dataIndex: 'index'}, 
                        { id: 'failure', header: _('Failure'), width: 60, sortable: false, dataIndex: 'message'}
                    ],
                    autoExpandColumn: 'failure'
                }]
            }],
            listeners: {
                scope: this,
                show: this.onSummaryPanelShow
            },
            
            /**
             * finish button handler for this panel
             */
            onFinishButton: (function() {
                if (! this.importMask) {
                    this.importMask = new Ext.LoadMask(this.getEl(), {msg: String.format(_('Importing {0}'), this.recordClass.getRecordsName())});
                }
                this.importMask.show();
                
                // collect client data
                var clientRecordData = [];
                var importOptions = {};
                
                this.exceptionStore.clearFilter(true);
                this.exceptionStore.each(function(r) {
                    clientRecordData.push({
                        recordData: r.get('resolvedRecord').data,
                        resolveStrategy: r.get('resolveStrategy') || 'discard',
                        index: r.get('index')
                    });
                });
                
                this.doImport(function(request, success, response) {
                    this.importMask.hide();
                    
                    this.fireEvent('finish', this, this.layout.activeItem);
                    
                    if (Ext.isArray(response.exceptions) && response.exceptions.length > 0) {
                        // show errors and fence finish/back btn
                        this.backButton.setDisabled(true);
                        this.finishButton.setHandler(function() {this.window.close()}, this);
                        
                        this.summaryPanelInfo.hide();
                        this.exceptionStore.clearFilter(true);
                        
                        this.summaryPanelFailures.show();
                        this.summaryPanelFailures.setTitle(String.format(_('{0} records had failures and where discarded.'), response.exceptions.length));
                    } else {
                        this.window.close();
                    }
                }, importOptions, clientRecordData);
                
            }).createDelegate(this)
            
        }
    },
    
    /**
     * summary panel show handler
     */
    onSummaryPanelShow: function() {
        if (! this.summaryPanelInfo.rendered) {
            return this.onSummaryPanelShow.defer(100, this);
        }

        // calc metrics
        var rsp = this.lastImportResponse,
            totalcount = rsp.totalcount,
            failcount = 0,
            mergecount = 0,
            discardcount = 0;
            
        this.exceptionStore.clearFilter();
        this.exceptionStore.each(function(r) {
            var strategy = r.get('resolveStrategy');
            if (! strategy || !Ext.isString(strategy)) {
                failcount++;
            } else if (strategy == 'keep') {
                totalcount++;
            } else if (strategy.match(/^merge.*/)) {
                mergecount++;
            } else if (strategy == 'discard') {
                discardcount++;
            }
        }, this);
        
        var tags = this.tagsPanel.getFormField().getValue(),
            container = this.containerCombo.selectedContainer,
            info = [String.format(_('In total we found {0} records in your import file.'), rsp.totalcount + rsp.duplicatecount + rsp.failcount)];
            
            if (totalcount) {
                info.push(String.format(_('{0} of them will be added as new records into: "{1}".'), 
                    totalcount, 
                    Tine.Tinebase.common.containerRenderer(container).replace('<div', '<span').replace('</div>', '</span>')
                ));
            }
            
            if (mergecount + discardcount) {
                info.push(String.format(_('{0} of them where identified as duplicates.'), mergecount + discardcount));
                
                if (mergecount) {
                    info.push(String.format(_('From the identified duplicates {0} will be merged into the existing records.'), mergecount));
                }
                
                if (discardcount) {
                    info.push(String.format(_('From the identified duplicates {0} will be discarded.'), discardcount));
                }
            }
            
            if (Ext.isArray(tags) && tags.length) {
                var tagNames = [],
                    tagRecord = null;
                Ext.each(tags, function(tag) {
                    if (Ext.isString(tag)) {
                        tagRecord = this.tagsPanel.recordTagsStore.getById(tag);
                        tag = (tagRecord) ? tagRecord.data : null;
                    }
                    if (tag) {
                        tagNames.push(tag.name)
                    }
                }, this);
                info.push(String.format(_('All records will be tagged with: "{0}" so you can find them easily.'), tagNames.join(', ')));
            }
            
        this.summaryPanelInfo.update('<div style="padding: 5px;">' + info.join('<br />') + '</div>');
        
        // failures
        if (failcount) {
            this.exceptionStore.filterBy(function(record, id) {
                return ! (record.get('code') == 629 && ! record.get('isResolved'));
            });
            this.summaryPanelFailures.show();
            this.summaryPanelFailures.setTitle(String.format(_('{0} records have failures and will be discarded.'), failcount));
            
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
