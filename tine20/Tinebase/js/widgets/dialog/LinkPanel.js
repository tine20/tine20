/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * link display panel
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.LinkPanel
 * @extends     Ext.Panel
 * 
 * <p>Link Panel</p>
 * <p>to be used as tab panel in edit dialogs
 * <pre>
 * </pre>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.dialog.LinkPanel
 */
Tine.widgets.dialog.LinkPanel = Ext.extend(Ext.Panel, {
    
    //private
    frame: true,
    border: true,
    autoScroll: true,
    
    relatedRecords: null,
    
    /**
     * @private
     */
    initComponent: function() {
        this.title = _('Links');
        
        this.store = new Ext.data.JsonStore({
            id: 'id',
            fields: Tine.Tinebase.Model.Relation,
            sortInfo: {
                field: 'related_model',
                direction: 'DESC'
            }
        });

        this.initLinksDataView();
        this.items = [this.linksDataView];
        Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.registerSkipItem(this);
       
        Tine.widgets.dialog.LinkPanel.superclass.initComponent.call(this);
    },
    
    /**
     * add on click event after render
     * @private
     */
    afterRender: function() {
        Tine.widgets.dialog.LinkPanel.superclass.afterRender.apply(this, arguments);
        
        this.body.on('click', this.onClick, this);
    },
    
    /**
     * 
     * @param {Object} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        
        if (record.get('relations')) {
            this.store.loadData(record.get('relations'), true);
        }
    },
    
    /**
     * init activities data view
     */
    initLinksDataView: function() {
        var linksTpl = new Ext.XTemplate(
            '<tpl for=".">',
               '<div class="x-widget-links-linkitem" id="{id}">',
                    '<div class="x-widget-links-linkitem-text">',
                        //' ext:qtip="{related_model}">',
                        '{[this.render(values.related_record, values.related_model, values.type, values.id)]}<br/>',
                    '</div>',
                '</div>',
            '</tpl>' ,{
                relatedRecords: this.relatedRecords,
                render: function(value, model, type, id) {
                    var result = '',
                        record = null;
                    if (this.relatedRecords[model]) {
                        Tine.log.debug('Tine.widgets.dialog.LinkPanel::initLinksDataView - showing link for ' + model);
                        record = new this.relatedRecords[model].recordClass(value);
                        result = record.modelName 
                            + ' ( <i>' + type + '</i> ): <a class="tinebase-relation-link" href="#" id="' + id + ':' + model + '">' 
                            + Ext.util.Format.htmlEncode(record.getTitle()) + '</a>';
                    } else {
                        Tine.log.warn('Tine.widgets.dialog.LinkPanel::initLinksDataView - ' + model + ' does in exist in related records!');
                    }
                    return result;
                }
            }
        );
        
        this.linksDataView = new Ext.DataView({
            anchor: '100% 100%',
            tpl: linksTpl,       
            //id: 'grid_links_limited',
            store: this.store,
            overClass: 'x-view-over',
            itemSelector: 'activities-item-small' // don't forget that
        });
    },
    
    /**
     * on click for opening edit related object dlg
     * 
     * @param {} e
     * @private
     */
    onClick: function(e) {
        target = e.getTarget('a[class=tinebase-relation-link]');
        if (target) {
            var idParts = target.id.split(':');
            var record = this.store.getById(idParts[0]).get('related_record');
            
            var popupWindow = this.relatedRecords[idParts[1]].dlgOpener({
                record: new this.relatedRecords[idParts[1]].recordClass(record)
            });
        }
    }
});
