/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * Contract edit dialog
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.LinkPanel
 * @extends     Ext.Panel
 * 
 * <p>Link Panel</p>
 * <p>to be used as tab panel in edit dialogs
 * <pre>
 * TODO         add anchor/button to jump to related object
 * TODO         improve display of linked objects (show correct title / more information)
 * </pre>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
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
        
        Tine.widgets.dialog.LinkPanel.superclass.initComponent.call(this);
    },
    
    /**
     * 
     * @param {Object} record
     */
    onRecordLoad: function(record) {
        this.record = record;
        
        //console.log(record.get('relations'));
        
        this.store.loadData(record.get('relations'), true);
    },
    
    /**
     * init activities data view
     */
    initLinksDataView: function() {
        
        var linksTpl = new Ext.XTemplate(
            '<tpl for=".">',
               '<div class="x-widget-links-linkitem" id="{id}">',
                    '<div class="x-widget-links-linkitem-text" ext:qtip="{related_model}">',
                        '{[this.render(values.related_record)]}<br/>',
                    //    'ext:qtip="{[this.render(values.related_model)]}>',
                    //    '{[this.encode(values.related_id)]}<hr color="#aaaaaa">',
                    /*
                    '   ext:qtip="{[this.encode(values.note)]} - {[this.render(values.creation_time, "timefull")]} - {[this.render(values.created_by, "user")]}" >', 
                        '{[this.render(values.note_type_id, "icon")]}&nbsp;{[this.render(values.creation_time, "timefull")]}<br/>',
                        '{[this.encode(values.note, true)]}<hr color="#aaaaaa">',
                    */
                    '</div>',
                '</div>',
            '</tpl>' ,{
                encode: function(value, ellipsis) {
                    var result = Ext.util.Format.nl2br(Ext.util.Format.htmlEncode(value)); 
                    return (ellipsis) ? Ext.util.Format.ellipsis(result, 300) : result;
                },
                render: function(value, type) {
                    //console.log(value);
                    
                    // TODO use related record here and getTitle/title property
                    
                    /*
                    switch (type) {
                        case 'icon':
                            return Tine.widgets.activities.getTypeIcon(value);
                        case 'user':
                            if (!value) {
                                value = Tine.Tinebase.registry.map.currentAccount.accountDisplayName;
                            }
                            var username = value;
                            return '<i>' + username + '</i>';
                        case 'time':
                            if (!value) {
                                return '';
                            }
                            return value.format(Locale.getTranslationData('Date', 'medium'));
                        case 'timefull':
                            if (!value) {
                                return '';
                            }
                            return value.format(Locale.getTranslationData('Date', 'medium')) + ' ' +
                                value.format(Locale.getTranslationData('Time', 'medium'));
                    }
                                */
                    return value.lead_name;
                }
            }
        );
        
        this.linksDataView = new Ext.DataView({
            anchor: '100% 100%',
            tpl: linksTpl,       
            id: 'grid_links_limited',
            store: this.store,
            overClass: 'x-view-over',
            itemSelector: 'activities-item-small'
        }); 
    }
});
