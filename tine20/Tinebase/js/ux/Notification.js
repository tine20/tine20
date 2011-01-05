/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * 
 * TODO         play sound
 * TODO         add webkit notifications -> this has to be resolved first: http://code.google.com/p/chromium/issues/detail?id=31736
 *              http://dev.w3.org/2006/webapi/WebNotifications/publish/
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
//            } else if (window.webkitNotifications !== undefined) {
//                console.log('webkitNotifications available');
//                if (window.webkitNotifications.checkPermission() == 0) {
//                    // you can pass any url as a parameter
//                    window.webkitNotifications.createNotification(iconUrl, title, text).show(); 
//                } else {
//                    window.webkitNotifications.requestPermission();
//                }
//                var nc = window.webkitNotifications;
//                document.tempNotif = nc.createNotification(iconUrl, title, text);
//                document.tempNotif.ondisplay = function() {
//                    setTimeout(function(){
//                        document.tempNotif.cancel();
//                        document.tempNotif = false;
//                    },5000); 
//                };
//                document.tempNotif.show();
//                
//                var nc = window.webkitNotifications;
//                if (nc.checkPermission()) { 
//                    //console.log('request permission for ');
//                    //console.log(webkitNotification);
//                    //nc.requestPermission(webkitNotification);
//                    nc.requestPermission(Ext.ux.Notification.webkit); 
//                } else { 
//                    webkitNotification(iconUrl, title, text);
//                }
                
            // default behaviour
            } else {
                Ext.ux.MessageBox.msg(title, text);
            }
        }
    };
}();
