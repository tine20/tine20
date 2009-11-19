/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:GridPanel.js 7170 2009-03-05 10:58:55Z p.schuele@metaways.de $
 *
 * 
 * TODO         play sound
 * TODO         add googlegears support
 * TODO         activate default notifications?
 */    
 
Ext.namespace('Ext.ux.Notification');
    
Ext.ux.Notification = function(){
    /*
    var msgDiv;

    function createBox(t, s){
        return ['<div class="msg">',
                '<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>',
                '<div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc"><h3>', t, '</h3>', s, '</div></div></div>',
                '<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>',
                '</div>'].join('');
    }
    */
    
    return {
        show: function(title, text){
            
            // define icon url
            // TODO use relative path here
            var iconUrl = window.location.href.replace(/#+.*/, '') + '/images/tine_logo.gif';
            
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
                
            // default behaviour
            } else {
                
                // TODO     make sliding in of notifications work correctly / beautify notfications
                
                /*
                if(! msgDiv){
                    msgDiv = Ext.DomHelper.insertBefore(document.body, {id:'msg-div'}, true);
                }
                msgDiv.alignTo(document, 't-t');
                var box = Ext.DomHelper.append(msgDiv, {html:createBox(title, text)}, true);
                box.slideIn('t').pause(4).ghost("t", {remove:true});
                */
                
                /*
                if(! msgDiv){
                    msgDiv = Ext.DomHelper.insertAfter(document.body, {id:'msg-div'}, true);
                }
                msgDiv.alignTo(document, 'b-b');
                var box = Ext.DomHelper.append(msgDiv, {html:createBox(title, text)}, true);
                box.slideIn('b').pause(2).ghost("b", {remove:true});
                */
            }
        }
    };
}();

