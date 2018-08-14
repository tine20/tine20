/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         add preference for sending mails with felamimail or mailto?
 */

Ext.ns('Tine.Addressbook');

/**
 * the details panel (shows contact details)
 *
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ContactGridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 */
Tine.Addressbook.ContactGridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {

    il8n: null,
    felamimail: false,

    recordClass: Tine.Addressbook.Model.Contact,

    getSingleRecordPanel: function() {
        var me = this;
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Tine.widgets.display.RecordDisplayPanel({
                recordClass: this.recordClass,
                getBodyItems: function() {
                    return [{
                        layout: 'hbox',
                        flex: 1,
                        border: false,
                        layoutConfig: {
                            padding: '0',
                            align: 'stretch'
                        },
                        defaults: {
                            margins: '0 5 0 0'
                        },
                        items: [{
                            width: 90,
                            layout: 'ux.display',
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'jpegphoto',
                                cls: 'preview-panel-image',
                                anchor:'100% 100%',
                                hideLabel: true,
                                htmlEncode: false,
                                renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'image', 'displayPanel').createDelegate(me)
                            }]
                        }, {
                            flex: 1,
                            layout: 'ux.display',
                            labelWidth: 60,
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                layout: 'hbox',
                                border: false,
                                anchor: '100% 100%',
                                layoutConfig: {
                                    align: 'stretch'
                                },
                                items: [{
                                    layout: 'ux.display',
                                    layoutConfig: {
                                        background: 'inner',
                                        labelLWidth: 100,
                                        declaration: this.app.i18n._('Business')
                                    },
                                    labelAlign: 'top',
                                    border: false,
                                    flex: 1,
                                    items: [{
                                        xtype: 'ux.displayfield',
                                        name: 'org_name',
                                        hideLabel: true,
                                        htmlEncode: false,
                                        renderer: function(value) {
                                            return '<b>' +  Tine.Tinebase.EncodingHelper.encode(value) + '</b>';
                                        }
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'dtstart',
                                        hideLabel: true,
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'addressblock', 'displayPanel').createDelegate(me, {
                                            'street': 'adr_one_street',
                                            'street2': 'adr_one_street2',
                                            'postalcode': 'adr_one_postalcode',
                                            'locality': 'adr_one_locality',
                                            'region': 'adr_one_region',
                                            'country': 'adr_one_countryname'
                                        }, true)
                                    }]
                                }, {
                                    layout: 'ux.display',
                                    layoutConfig: {
                                        background: 'inner'
                                    },
                                    labelWidth: 50,
                                    flex: 1,
                                    border: false,
                                    items: [{
                                        xtype: 'ux.displayfield',
                                        name: 'tel_work',
                                        fieldLabel: this.app.i18n._('Phone')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'tel_cell',
                                        fieldLabel: this.app.i18n._('Mobile')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'tel_fax',
                                        fieldLabel: this.app.i18n._('Fax')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'email',
                                        fieldLabel: this.app.i18n._('E-Mail'),
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'email', 'displayPanel').createDelegate(me)
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'url',
                                        fieldLabel: this.app.i18n._('Web'),
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'url', 'displayPanel').createDelegate(me)
                                    }]
                                }]
                            }]
                        }, {
                            flex: 1,
                            layout: 'ux.display',
                            labelWidth: 60,
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                layout: 'hbox',
                                border: false,
                                anchor: '100% 100%',
                                layoutConfig: {
                                    align: 'stretch'
                                },
                                items: [{
                                    layout: 'ux.display',
                                    layoutConfig: {
                                        background: 'inner',
                                        labelLWidth: 100,
                                        declaration: this.app.i18n._('Private')
                                    },
                                    labelAlign: 'top',
                                    border: false,
                                    flex: 1,

                                    // @todo: this field doesn't actually require a certain field, there should be two methods for RenderManager:
                                    //  + get()
                                    //  + getBlock() // block actually doesn't specify a certain field and only an record, the field declaration should come from the modelconfig later
                                    items: [{
                                        xtype: 'ux.displayfield',
                                        name: 'attendee',
                                        hideLabel: true,
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'addressblock', 'displayPanel').createDelegate(me, {
                                            'street': 'adr_two_street',
                                            'street2': 'adr_two_street2',
                                            'postalcode': 'adr_two_postalcode',
                                            'locality': 'adr_two_locality',
                                            'region': 'adr_two_region',
                                            'country': 'adr_two_countryname'
                                        }, true)
                                    }]
                                }, {
                                    layout: 'ux.display',
                                    layoutConfig: {
                                        background: 'inner'
                                    },
                                    labelWidth: 50,
                                    flex: 1,
                                    border: false,
                                    items: [{
                                        xtype: 'ux.displayfield',
                                        name: 'tel_home',
                                        fieldLabel: this.app.i18n._('Phone')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'tel_cell_private',
                                        fieldLabel: this.app.i18n._('Mobile')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'tel_fax_home',
                                        fieldLabel: this.app.i18n._('Fax')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'email_home',
                                        fieldLabel: this.app.i18n._('E-Mail'),
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'email', 'displayPanel').createDelegate(me)
                                    }]
                                }]
                            }]
                        }, {
                            flex: 1,
                            layout: 'fit',

                            border: false,
                            items: [{
                                cls: 'x-ux-display-background-border',
                                xtype: 'ux.displaytextarea',
                                name: 'note'
                            }]
                        }]
                    }];
                }
            });
        }

        return this.singleRecordPanel;
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        Tine.Addressbook.ContactGridDetailsPanel.superclass.afterRender.apply(this, arguments);

        if (this.felamimail === true) {
            this.body.on('click', this.onClick, this);
        }
    },

    /**
     * on click for compose mail
     *
     * @param {} e
     *
     * TODO check if account is configured?
     * TODO generalize that
     */
    onClick: function(e) {
        var target = e.getTarget('a[class=tinebase-email-link]');
        if (target) {
            var email = target.id.split(':')[1];
            var defaults = Tine.Felamimail.Model.Message.getDefaultData();
            defaults.to = [email];
            defaults.body = Tine.Felamimail.getSignature();

            var record = new Tine.Felamimail.Model.Message(defaults, 0);

            Tine.Felamimail.MessageEditDialog.openWindow({
                record: record
            });
        }
    }
});
