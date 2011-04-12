/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * 
 * TODO         play sound
 */    
 
Ext.ns('Ext.ux.Notification');
    
Ext.ux.Notification = function(){
    return {
        show: function(title, text){
            
            // define icon url
            // TODO use relative path here
            var iconUrl = window.location.href.replace(/#+.*/, '') + '/images/tine_logo.png';
            
            // JETPACK firefox extension (@link https://jetpack.mozillalabs.com/)
            if (window.jetpack !== undefined) {
                jetpack.notifications.show({
                    title: title, 
                    body: text, 
                    icon: iconUrl
                });
                
            // CALLOUT firefox extension (@link http://github.com/lackac/callout)
            } else if (window.callout !== undefined) {
                callout.notify(title, text, {
                    icon: iconUrl,
                    href: document.location.href
                });
                
            // webkit notifications
            } else if (window.webkitNotifications !== undefined && window.webkitNotifications.checkPermission() == 0) { // 0 is PERMISSION_ALLOWED
                var notification = window.webkitNotifications.createNotification(iconUrl, title, text);
                notification.show();
                setTimeout(function(){
                    notification.cancel();
                }, 15000);
                
            // default behaviour
            } else {
                Ext.ux.MessageBox.msg(title, text);
            }
        }
    };
}();
