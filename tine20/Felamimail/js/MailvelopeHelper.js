Tine.Felamimail.mailvelopeHelper = function() {

    return {
        //give mailvelope some seconds to load
        mailvelopeLoaded: Promise.race([
            new Promise(function (fullfill, reject) {
                if (typeof mailvelope !== 'undefined') {
                    fullfill();
                } else {
                    Ext.EventManager.addListener(window, 'mailvelope', fullfill);
                }
            }), new Promise(function (fullfill, reject) {
                (function () {
                    reject(new Error("mailvelope not available. (don't panic this is not a problem you just can't work with crypted emails using mailvelope)"));
                }).defer(3000);
            })]
            // no catch here!!! otherwise the promise is fulfilled all the time!
        ),

        getKeyring: function() {
            return this.mailvelopeLoaded.then(function() {
                return new Promise(function(fullfill, reject) {
                    var identifier = Tine.Tinebase.common.getUrl();
                    mailvelope.getKeyring(identifier).then(fullfill, function(err) {
                        mailvelope.createKeyring(identifier).then(fullfill);
                    }).then(function(keyring) {
                        // @TODO get dataurl of tine20logo
                        //keyring.setLogo('data:image/png;base64,iVBORS==', 3)
                    });
                });
            });
        }
    }
}();
