/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * 
 * TODO         play sound / vibrate?
 * TODO         allow to set display duration?
 */    
 
Ext.ns('Ext.ux.Notification');
    
Ext.ux.Notification = function(){
    return {
        show: function(title, text){
            
            // define icon url
            // TODO use relative path here
            var iconUrl = window.location.href.replace(/#+.*/, '') + '/images/tine_logo.png';
            
            // webkit notifications
            if (window.webkitNotifications !== undefined && window.webkitNotifications.checkPermission() == 0) { // 0 is PERMISSION_ALLOWED
                var notification = window.webkitNotifications.createNotification(iconUrl, title, text);
                notification.show();
                setTimeout(function () {
                    notification.cancel();
                }, 15000);

            // Notification (see https://notifications.spec.whatwg.org/)
            } else if (window.Notification && window.Notification.permission == 'granted') {
                var notification = new window.Notification(title, {
                    icon: iconUrl,
                    body: text
                });

                //notification.onclick = function () {
                //    window.open("http://stackoverflow.com/a/13328397/1269037");
                //};
                
            // default behaviour
            } else {
                Ext.ux.MessageBox.msg(title, text);
            }
        }
    };
}();
