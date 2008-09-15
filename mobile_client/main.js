
Ext.onReady(function() {
    var connection = new Tine.iPhoneClient.Connection({
        url: '/tine20/index.php'
    });
    
    connection.login('tine20admin', 'lars', function() {
        new Ext.Viewport({
            layout: 'fit',
            items: {
                xtype: 'panel',
                layout: 'fit',
                buttonAlign: 'center',
                tbar: [
                    {text: 'Settings', handler: function() {}},
                    '->',
                    {text: 'Help', handler: function() {}}
                ],
                buttons: [
                    {text: 'List', handler: function() {}, pressed: true },
                    {text: 'Day', handler: function() {}},
                    {text: 'Month', handler: function() {}}
                ],
                bbar: [
                    {text: 'foo', handler: function() {}},
                    {text: 'bar', handler: function() {}},
                ],
                items: new Tine.iPhoneClient.Tasks.MainGrid({})
            }
        });
    });
});

