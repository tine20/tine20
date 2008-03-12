/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
Ext.namespace('Tine', 'Tine.Tinebase');

Tine.Tinebase.UserRegistration = Ext.extend(Ext.Window, {
    name: 'userRegistration',
    title: 'Registration Wizzard',
    //layout: 'card',
    //activeItem: 0,
    layout: 'fit',
    width: 400,
    height: 400,
    /**
     * holds reg form (card layout)
     */
    regForm: false,
    
    /**
     * @private
     */
    initComponent: function() {
        this.items = [this.getWizard()];
        Tine.Tinebase.UserRegistration.superclass.initComponent.call(this);
        
    },
    navHandler: function(button) {
        var direction = 1;
        if (button.id == 'move-prev') {
            direction = -1;
        }
        var cl = Ext.getCmp('UserRegistrationCardLayout');
        //cl.setActiveItem(cl.activeItem + direction);
        console.log(cl);
        //this.getRegForm().setActiveItem(this.regFrom.activeItem + direction);
    },
    /**
     * First card, all about names
     * 
     * @todo Ajax validation of accountLoginName is a bit complex, as ExtJS don't support syncrous requests.
     * As such, we have to introduce a valid flag. If the user presses the next button, and the valid flag is not
     * set, we have to ask the ajax-request if it's running, if yes, display a waitbar, if no ask the user to change the name.
     * Therefore we also need to add a on-change listener to the filed.
     * 
     * @todo username change is not working yet
     */
    cardNames: function () {
        
        var accountFirstName = new Ext.form.TextField({
            fieldLabel: 'Given name',
            name: 'accountFirstName',
            id: 'accountFirstName',
            allowBlank: true
        });
        var accountLastName = new Ext.form.TextField({
            fieldLabel: 'Family name',
            name: 'accountLastName',
            id: 'accountLastName',
            allowBlank: false
        });
        var accountLoginName = new Ext.form.TextField({
            fieldLabel: 'Login name',
            name: 'accountLoginName',
            id: 'accountLoginName',
            //validator: function(accountLoginName) {
            //    
            //}
            allowBlank: false
        });
        
        // suggest an accountLoginName
        accountLoginName.on('focus', function(accountLoginName) {
            if (!accountLoginName.getValue()) {
                var cardNamesValues = Ext.getCmp('cardNames').getForm().getValues();
                Ext.Ajax.request({
                    url: 'index.php',
                    //method: POST, (should not be required)
                    params: {
                        method: 'Tinebase_UserRegistration.suggestUsername',
                        regData: Ext.util.JSON.encode(cardNamesValues)
                    },
                    success: function(result, request) {
                    	accountLoginName.setValue( Ext.util.JSON.decode(result.responseText));
                        // hack to detect user change
                        accountLoginName.originalValue = accountLoginName.getValue();
                        this.accountLoginName = accountLoginName.getValue();
                    },
                    failure: function (result, request) { 
                        
                    },
                    scope: this
                });
            }
        }, this);
        
        accountLoginName.on('change', function(accountLoginName) {
            //console.log('hallo');
        }, this);
        
        var cardNamesPanel = new Ext.form.FormPanel({
            id: 'cardNames',
            layout: 'form',
            bodyStyle: 'paddingLeft:15px',
            anchor:'100%',
            defaults: {
                anchor: '95%',
                xtype: 'textfield'
            },
            items: [
                accountFirstName,
                accountLastName,
                accountLoginName
            ]
        });
        
        return cardNamesPanel;
    },
    /**
     * @private 
     */
    getWizard: function() {
        if (! this.wizard) {
    
            this.wizard = new Ext.ux.Wizard({
                id: 'myWizard',
                //title: 'My Example Wizard',
                mandatorySteps: 2, // at least two steps are required
    
                // the panels (or "cards") within the layout
                items: [
                    this.cardNames(),
                {
                    id: 'card-1',
                    style: {'padding': '5px'},
                    html: '<p>Step 2 of 5</p>'
                },
                {
                    id: 'card-2',
                    style: {'padding': '5px'},
                    html: '<p>Step 3 of 5</p>'
                },{
                    id: 'card-3',
                    style: {'padding': '5px'},
                    html: '<p>Step 4 of 5</p>'
                },{
                    id: 'card-4',
                    style: {'padding': '5px'},
                    html: '<h1>Congratulations!</h1><p>Step 5 of 5 - Complete</p>'
                }]
            });
            
            
            this.wizard.on({
                'leave': {
                    fn: function(currentItem, nextItem, forward) {
                        switch(currentItem.id) {
                            case 'cardNames':
                            	// check valid username
                            	console.log('checking username .... ' + this.accountLoginName );
                            	//-- i'll set it to true for the moment (till the synchronous ajax function works)                            	
								//var validUsername = false;
                            	var validUsername = true;
                            	
								if ( this.accountLoginName ) {
					                Ext.Ajax.request({
					                    url: 'index.php',
					                    params: {
					                        method: 'Tinebase_UserRegistration.checkUniqueUsername',
					                        username: Ext.util.JSON.encode(this.accountLoginName)
					                    },
					                    success: function(result, request) {
					                    	console.log('username check result: ' + Ext.util.JSON.decode(result.responseText));
					                    	// get result
					                    	if ( Ext.util.JSON.decode(result.responseText) == true ) {
					                    		validUsername = true;
					                    	}
					                    	console.log ( 'validUsername = ' + validUsername );
					                    },
					                    failure: function (result, request) { 
					                        
					                    },
					                    scope: this
					                });
								}

								console.log ( 'validUsername before check = ' + validUsername );
								
								//-- wait for ajax request to finish or use callback function
								// see: http://extjs.com/forum/showthread.php?t=27427
								
                				if ( !this.accountLoginName || !validUsername ) {
                                    Ext.Msg.show({
                                       title:'Login name',
                                       msg: 'The login name you chose is not valid. Please choose a valid login name.',
                                       buttons: Ext.Msg.OK,
                                       //fn: processResult,
                                       animEl: 'elId',
                                       icon: Ext.MessageBox.INFO
                                    });
                                    return false;
                                } else {
                                	// register new user!
                                	//-- just testing -> this is going to happen in step/card 2-4
                                	//-- with ajax request??
                                	
                                	// get values in array 
                                	//-- we need more values here (i.e. email address)
                                	var cardNamesValues = Ext.getCmp('cardNames').getForm().getValues();
					                Ext.Ajax.request({
					                    url: 'index.php',
					                    params: {
					                        method: 'Tinebase_UserRegistration.registerUser',
					                        regData: Ext.util.JSON.encode(cardNamesValues)
					                    },
					                    success: function(result, request) {
					                    	console.log('creating new user account...' );
					                    	// get result
					                    },
					                    failure: function (result, request) { 
					                        
					                    },
					                    scope: this
					                });
                                }
                        }
                        //console.log(currentItem);
                            //var msg = 'Leaving ' + currentItem.id + ', entering ' + nextItem.id
                            //        + '\nAre you sure you want to do that?';
                             
                            //return forward ? window.confirm(msg) : true;
                          },
                    scope: this 
                },
                'activate': {
                    fn: function(currentItem) {
                            //Ext.MessageBox.alert('Wizard', 'Entering ' + currentItem.id);
                          },
                    scope: this
                }, 
                'cancel': {
                    fn: function() {
                        this.close();
                            //Ext.MessageBox.alert('Wizard', 'Cancel');
                    },
                    scope: this
                },
                'finish': {
                    fn: function() {
                            //Ext.MessageBox.alert('Wizard', 'Finish');
                    },
                    scope: this
                }    
            }, this);
        }
        return this.wizard;
    }
});