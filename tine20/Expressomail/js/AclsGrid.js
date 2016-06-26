/*
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.AclsGrid
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Account Edit Dialog</p>
 * <p>
 * </p>
 * 
 * @author      Bruno Vieira Costa <bruno.vieira-costa@serpro.gov.br>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Expressomail.AclsGrid
 * 
 */
Tine.Expressomail.AclsGrid = Ext.extend(Tine.widgets.account.PickerGridPanel, {

    /**
     * Tine.widgets.account.PickerGridPanel config values
     */
    selectType: 'user',
    selectTypeDefault: 'user',
    hasAccountPrefix: true,
    recordClass: Tine.Expressomail.Model.Acl,
    enableSendAs: false,
    
    /**
     * @private
     */
    initComponent: function () {
        this.initColumns();
        
        Tine.Expressomail.AclsGrid.superclass.initComponent.call(this);
    },
    
    initColumns: function() {
        this.configColumns = [
            new Ext.ux.grid.CheckColumn({
                header: i18n._('Read'),
                tooltip: i18n._('Read messages from folders'),
                dataIndex: 'readacl',
                width: 55
            }),
            new Ext.ux.grid.CheckColumn({
                header: i18n._('Write'),
                tooltip: i18n._('Write and delete messages from folders'),
                dataIndex: 'writeacl',
                width: 55
            })
        ];
        if (this.enableSendAs) {
            this.configColumns.push(new Ext.ux.grid.CheckColumn({
                header: i18n._('Send as'),
                tooltip: i18n._('Send as folder owner'),
                dataIndex: 'sendacl',
                width: 55,
            }));
        }
    }
});