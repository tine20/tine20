/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.dialog');

Tine.Tinebase.widgets.dialog.PasswordDialog = Ext.extend(Ext.Panel, {
    layout: 'fit',
    border: false,
    frame: false,

    /**
     * ok button action held here
     */
    okAction: null,

    /**
     * Allow to proceed with an empty password
     */
    allowEmptyPassword: false,

    /**
     * Entered password
     */
    password: null,

    /**
     * Constructor.
     */
    initComponent: function () {
        this.addEvents(
            /**
             * If the dialog will close and a password were choisen
             * @param node
             */
            'passwordEntered'
        );

        this.items = [{
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: .333
                },
                items: [
                    [{
                        columnWidth: 1,
                        xtype: 'tw-passwordTriggerField',
                        fieldLabel: i18n._('Password'),
                        name: 'password',
                        maxLength: 100,
                        allowBlank: false,
                        listeners: {
                            scope: this,
                            keyup: this.onChange.createDelegate(this)
                        }
                    }]
                ]
            }]
        }];

        var me = this;
        this.okAction = new Ext.Action({
            disabled: !this.allowEmptyPassword,
            text: 'Ok',
            iconCls: 'action_saveAndClose',
            minWidth: 70,
            handler: this.onOk.createDelegate(me),
            scope: this
        });

        this.bbar = [
            '->',
            this.okAction
        ];

        Tine.Filemanager.FilePickerDialog.superclass.initComponent.call(this);
    },

    /**
     * Disable ok button if no password entered
     * @param el
     */
    onChange: function (el) {
        this.password = el.getValue();
        this.okAction.setDisabled(!this.allowEmptyPassword && el.getValue().length === 0)
    },

    /**
     * button handler
     */
    onOk: function () {
        this.fireEvent('passwordEntered', this.password);
        this.window.close();
    },

    /**
     * Creates a new pop up dialog/window (acc. configuration)
     *
     * @returns {null}
     */
    openWindow: function () {
        this.window = Tine.WindowFactory.getWindow({
            title: i18n._('Set password'),
            closeAction: 'close',
            modal: true,
            width: 400,
            height: 150,
            layout: 'fit',
            plain: true,
            bodyStyle: 'padding:5px;',

            items: [
                this
            ]
        });

        return this.window;
    }
});