/*
 * Tine 2.0
 *
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Fernando Lages <fernando.lages@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2014 Serpro (http://serpro.gov.br)
 */

/**
 *
 * @class Tine.MultiWindow
 * @extends Ext.Window
 * for now it works pretty much the same as Ext.Window
 * but implements a notification tool on top of window
 * TODO: implement multiple windows feature
 */
Tine.MultiWindow = Ext.extend(Ext.Window, {
    notifiable : false,

    /**
     * initialize tools
     */
    initTools : function(){
        if(this.headerAsText && this.notifiable){
            this.addTool({
                id: 'notify',
                handler: this.notify.createDelegate(this, [])
            });
        }
        Tine.MultiWindow.superclass.initTools.call(this);
    },

    /**
     * fire event notify
     */
    notify : function(){
        this.fireEvent('notify', this);
        return this;
    },

    /**
     * set tools notify
     */
    setNotify : function(notify, what){
        if(this.header && this.headerAsText && this.notifiable) {
            if (what) {
                notify_class = 'x-tool-notify-'+what;
            }
            else {
                if (notify==='') {
                    notify_class = 'x-tool-notify-clear';
                }
                else {
                    notify_class = 'x-tool-notify-default';
                }
            }
            this.tools.notify.removeClass('x-tool-notify-clear');
            this.tools.notify.removeClass('x-tool-notify-default');
            this.tools.notify.removeClass('x-tool-notify-error');
            this.tools.notify.addClass(notify_class);
            this.tools.notify.dom.innerHTML = notify;
            this.fireEvent('notifychange', this, notify);
        }
        return this;
    }

});

Ext.override(Ext.ux.WindowFactory, {
    /**
     * @private
     */
    getExtWindow: function (c) {
        // add titleBar
        c.height = c.height + 20;
        // border width
        c.width = c.width + 16;

        //limit the window size
        c.height = Math.min(Ext.getBody().getBox().height, c.height);
        c.width = Math.min(Ext.getBody().getBox().width, c.width);

        c.layout = c.layout || 'fit';
        c.items = {
            layout: 'card',
            border: false,
            activeItem: 0,
            isWindowMainCardPanel: true,
            items: [this.getCenterPanel(c)]
        }

        // we can only handle one window yet
        c.modal = true;

        // do not allow drag&drop outside container limits
        c.constrain = true;

        var win = new Tine.MultiWindow(c);
        c.items.items[0].window = win;

        // if initShow property is present and it is set to false don't show window, just return reference
        if (c.hasOwnProperty('initShow') && c.initShow === false) {
            return win;
        }

        win.show();
        return win;
    }
});