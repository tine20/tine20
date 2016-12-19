/*
 * Tine 2.0
 * 
 * @package     Expressomail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Mário César Kolling <mario.kolling@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 */

Ext.namespace('Tine.Expressomail');

/**
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.SignatureAppletPanel
 * @extends     Ext.widgets.Panel
 * 
 * @author      Mário César Kolling <mario.kolling@serpro.gov.br>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new AppletPanel
 */

 Tine.Expressomail.SignatureAppletPanel = Ext.extend(Ext.Panel, {
    
    messageModel: null,
    
    signedMessageModel: null, // todo: create this model.
    
    initComponent: function()
    {
        Tine.Expressomail.SignatureAppletPanel.superclass.initComponent.call(this);
    }
    
//    // methods
//    toApplet: function()
//    {
//        // call Applet passing => this.messageModel.toString();
//
//    },
//    
//    fromApplet: function(response)
//    {
//        // get response and send the signed message
//    }
    
});