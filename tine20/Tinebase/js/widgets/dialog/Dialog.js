/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.dialog');

/**
 * @namespace Tine.Tinebase
 * @class Tine.Tinebase.dialog.Dialog
 * @extends Ext.Panel
 */
Tine.Tinebase.dialog.Dialog = Ext.extend(Ext.FormPanel, {
    /**
     * @cfg {Number} windowWidth
     * width of dialog window
     */
    windowWidth: 300,
    /**
     * @cfg {Number} windowHeight
     * height of dialog window
     */
    windowHeight: 400,
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required if not appName is given)
     */
    app: null,
    /**
     * @cfg {String} appName
     * name of app (required if no app is given)
     */
    appName: null,
    /**
     * @cfg {String} OkButtonText
     * text of ok button (optional) default to _('Ok')
     */
    applyButtonText: '',
    /**
     * @cfg {String} cancelButtonText
     * text of cancel button (optional) defaults to _('Cancel')
     */
    cancelButtonText: '',
    /**
     * @cfg {String} canonicalName
     * canonical name of dialog (required) see getCanonicalPathSegment
     */
    canonicalName: '',

    cls: 'tw-editdialog',
    
    layout: 'fit',
    border: false,

    initComponent: function () {
        if (! this.app && this.appName) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }
        if (! this.appName && this.app) {
            this.appName = this.app.name;
        }

        this.fbar = ['->', {
            text: this.cancelButtonText ? this.app.i18n._hidden(this.cancelButtonText) : i18n._('Cancel'),
            minWidth: 70,
            ref: '../buttonCancel',
            scope: this,
            handler: this.onButtonCancel,
            iconCls: 'action_cancel'
        }, {
            text: this.applyButtonText ? this.app.i18n._hidden(this.applyButtonText) : i18n._('Ok'),
            minWidth: 70,
            ref: '../buttonApply',
            scope: this,
            handler: this.onButtonApply,
            iconCls: 'action_saveAndClose'
        }];

        Tine.Tinebase.dialog.Dialog.superclass.initComponent.call(this);
    },

    /**
     * template fn, implement to have specific data in the events
     */
    getEventData: Ext.emptyFn,

    onButtonCancel: function() {
        this.fireEvent.apply(this, ['cancel'].concat(this.getEventData('cancel')));
        if (this.window.closeAction !== 'hide') {
            this.purgeListeners();
            this.window.close();
        } else {
            this.window.hide();
        }

    },

    onButtonApply: function() {
        var eventData = this.getEventData('apply');
        if (this.fireEvent.apply(this, ['beforeapply'].concat(eventData)) !== false) {
            this.fireEvent.apply(this, ['apply'].concat(eventData));
            if (this.window.closeAction !== 'hide') {
                this.purgeListeners();
                this.window.close();
            } else {
                this.window.hide();
            }
        }
    },

    getCanonicalPathSegment: function () {
        return ['',
            this.appName,
            this.canonicalName,
        ].join(Tine.Tinebase.CanonicalPath.separator);
    }
});