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
                    reject();
                }).defer(5000);
            })]
        )['catch'](function() {
            Tine.log.info('mailvelope not available');
        }),

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
