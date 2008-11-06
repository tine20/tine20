/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Tinebase.widgets.app');

Tine.Tinebase.widgets.app.GridPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {String} appName
     * internal/untranslated app name (required)
     */
    appName: null,
    /**
     * @cfg {String} modelName
     * name of the model/record  (required)
     */
    modelName: null,
    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class  (required)
     */
    recordClass: null,
    /**
     * @cfg {String} idProperty
     * property of the id of the record
     */
    idProperty: 'id',
    /**
     * @cfg {String} titleProperty
     * property of the title attibute, used in generic getTitle function  (required)
     */
    titleProperty: null,
    /**
     * @cfg {String} containerItemName
     * untranslated container item name
     */
    containerItemName: 'record',
    /**
     * @cfg {String} containerItemsName
     * untranslated container items (plural) name
     */
    containerItemsName: 'records',
    /**
     * @cfg {String} containerName
     * untranslated container name
     */
    containerName: 'container',
    /**
     * @cfg {string} containerName
     * untranslated name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg {String} containerProperty
     * name of the container property
     */
    containerProperty: 'container_id',
    /**
     * @cfg {Array} actionToolbarItems
     * additional items for actionToolbar
     */
    actionToolbarItems: [],
    /**
     * @cfg {Array} contextMenuItems
     * additional items for contextMenu
     */
    contextMenuItems: [],
    
    /**
     * @property {Ext.Tollbar} actionToolbar
     */
    actionToolbar: null,
    /**
     * @property {Ext.Menu} contextMenu
     */
    contextMenu: null,
    
    /**
     * extend standart initComponent chain
     * @private
     */
    initComponent: function(){
        // init translations
        this.i18n = new Locale.Gettext();
        this.i18n.textdomain(this.appName);
        // init actions with actionToolbar and contextMenu
        this.initActions();


        
        Tine.Tinebase.widgets.app.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        this.action_editInNewWindow = new Ext.Action({
            requiredGrant: 'readGrant',
            text: String.format(this.i18n._('Edit {0}'), this.containerItemName),
            disabled: true,
            actionType: 'edit',
            handler: this.onEditInNewWindow,
            iconCls: 'action_edit',
            scope: this
        });
        
        this.action_addInNewWindow= new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: String.format(this.i18n._('Add {0}'), this.containerItemName),
            handler: this.onEditInNewWindow,
            iconCls: 'TasksIconCls',
            scope: this
        });
        
        this.action_deleteRecord = new Ext.Action({
            requiredGrant: 'deleteGrant',
            allowMultiple: true,
            singularText: String.format('Delete {0}', this.containerItemName),
            pluralText: String.format('Delete {0}', this.containerItemsName),
            translationObject: this.translation,
            text: String.format(this.i18n.ngettext('Delete {0}', 'Delete {1}', 1), this.containerItemName, this.containerItemsName),
            handler: this.onDeleteRecord,
            disabled: true,
            iconCls: 'action_delete',
            scope: this
        });
        
        var a = [
            this.action_addInNewWindow,
            this.action_editInNewWindow,
            this.action_deleteRecord
        ];
        
        this.actionToolbar = new Ext.Toolbar(a.concat(this.actionToolbarItems));
        this.contextMenu = new Ext.Menu(a.concat(this.contextMenuItems));
    },
    
    
});