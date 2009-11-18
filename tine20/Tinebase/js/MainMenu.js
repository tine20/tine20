Tine.Tinebase.MainMenu = function(config) {
    this.isPrism = 'platform' in window;
    Ext.apply(this, config);
    // NOTE: Prism has no top menu yet ;-(
    //       Only the 'icon menu' is implemented (right mouse button in dock(mac)/tray(other)
    /*if (this.isPrism) {
        window.platform.showNotification('title', 'text', 'images/clear-left.png');
        this.menu = window.platform.icon().title = 'supertine';
        //window.platform.menuBar.addMenu(“myMenu”);
        
        this.menu = window.platform.icon().menu;
        window.platform.icon().menu.addMenuItem("myItem", "My Item", function(e) { window.alert("My Item!"); });
        
        var sub = this.menu.addSubmenu('mytest', 'mytest');
        sub.addMenuItem('test', 'test', function() {alert('test');});
        
    } else { */
        this.menu = new Ext.Toolbar({
            id: this.id, 
            height: this.height,
            items: this.items
        });
        
        return this.menu;
    //}
};


