/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.dialog');

Tine.Tinebase.widgets.dialog.PasswordDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    /**
     * @cfg {Boolean} allowEmptyPassword
     * Allow to proceed with an empty password
     */
    allowEmptyPassword: false,

    /**
     * @cfg {Boolean} hasPwGen
     * dialog provides password generation action
     */
    hasPwGen: true,

    /**
     * @cfg {Boolean} locked
     * password field is locked (****) per default
     */
    locked: true,

    /**
     * @cfg {String} windowTitle
     * title text when openWindow is used
     */
    windowTitle: '',

    /**
     * @cfg {String} questionText
     * question label for user prompt
     */
    questionText: '',

    /**
     * @cfg {String} passwordFieldLabel
     * label of password field
     */
    passwordFieldLabel: '',

    /**
     * @property {Tine.Tinebase.widgets.form.PasswordTriggerField} passwordField
     * Password field
     */
    passwordField: null,

    layout: 'fit',
    border: false,


    /**
     * Constructor.
     */
    initComponent: function () {
        this.windowTitle = this.windowTitle || i18n._('Set password');

        this.items = [{
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                border: false,
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1
                },
                items: [
                    [{
                        xtype: 'tw-passwordTriggerField',
                        fieldLabel: this.passwordFieldLabel || i18n._('Password'),
                        name: 'password',
                        maxLength: 100,
                        allowBlank: this.allowEmptyPassword,
                        locked: this.locked,
                        clipboard: this.hasPwGen,
                        ref: '../../../../passwordField',
                        listeners: {
                            scope: this,
                            keyup: this.onChange,
                            keydown: this.onKeyDown
                        }
                    }]
                ]
            }]
        }];

        if (this.questionText) {
            this.items[0].items[0].items[0].unshift({
                xtype: 'label',
                text: this.questionText
            }, {
                xtype: 'label',
                html: '<br>'
            })
        }

        var me = this;

        this.pwgenAction = new Ext.Action({
            disabled: false,
            text: i18n._('Generate password'),
            minWidth: 70,
            iconCls: 'action_managePermissions',
            handler: this.onPWGen.createDelegate(me),
            scope: this
        });

        if (this.hasPwGen) {
            this.tbar = [
                this.pwgenAction
            ];
        }

        Tine.Tinebase.widgets.dialog.PasswordDialog.superclass.initComponent.call(this);
    },

    afterRender: function () {
        Tine.Tinebase.widgets.dialog.PasswordDialog.superclass.afterRender.call(this);
        this.buttonApply.setDisabled(!this.allowEmptyPassword);

        this.passwordField.focus(true, 100);
    },

    /**
     * Generate pw
     */
    onPWGen: function () {
        var policyConfig = Tine.Tinebase.configManager.get('downloadPwPolicy');

        config = {
            minLength: policyConfig ? policyConfig.pwPolicyMinLength : 12,
            minWordChars: policyConfig ? policyConfig.pwPolicyMinWordChars : 5,
            minUppercaseChars: policyConfig ? policyConfig.pwPolicyMinUppercaseChars : 1,
            minSpecialChars: policyConfig ? policyConfig.pwPolicyMinSpecialChars : 1,
            minNumericalChars: policyConfig ? policyConfig.pwPolicyMinNumbers : 1
        };

        var gen = new Tine.Tinebase.PasswordGenerator(config);

        this.passwordField.setValue(gen.generatePassword());
        // revalidate button
        this.onChange(this.passwordField);
    },

    onKeyDown: function(f, e) {
        if (e.getKey() === e.ENTER) {
            this.onButtonApply()
        }
    },

    /**
     * Disable ok button if no password entered
     * @param el
     */
    onChange: function (el) {
        this.buttonApply.setDisabled(!this.allowEmptyPassword && el.getValue().length === 0)
    },

    getEventData: function (event) {
        if (event === 'apply') {
            return this.passwordField.getValue();
        }
    },

    /**
     * Creates a new pop up dialog/window (acc. configuration)
     *
     * @returns {null}
     */
    openWindow: function (config) {
        config = config || {};
        this.window = Tine.WindowFactory.getWindow(Ext.apply({
            title: this.windowTitle,
            closeAction: 'close',
            modal: true,
            width: 400,
            height: 130 +
                (this.hasPwGen ? 20 : 0) +
                (Math.ceil(this.questionText.length/70) * 20),
            layout: 'fit',
            items: [
                this
            ]
        }, config));

        return this.window;
    }
});