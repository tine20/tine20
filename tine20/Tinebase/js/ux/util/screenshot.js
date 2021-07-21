Ext.ns('Ext.ux.screenshot');

/**
 *
 * @param {Window} win
 * @param {Object} options
 *      type: 'png',                // png|jpeg|webp(chrome only)
 *      dpi: 300,
 *      download: false,
 *      filename: 'screenshot',     // without extension
 *      grabMouse: false,
 * @return {Promise}
 */
Ext.ux.screenshot.get = function(win, options) {
    var _ = window.lodash;

    // defaults
    options = _.assign({}, {
        type: 'png',
        dpi: 300,
        download: false,
        filename: 'screenshot',
        grabMouse: false,
    }, options);

    return new Promise(function (fulfill, reject) {
            require.ensure(["html2canvas"], function() {
                var html2canvas = require ("html2canvas");

                html2canvas(win.document.body, {
                    grabMouse: options.grabMouse,
                    scale: options.dpi/96
                }).then(function(canvas) {
                    var mimeType = 'image/' + options.type,
                        dataUrl = canvas.toDataURL(mimeType);

                    if (options.download) {
                        var downloadLink = document.createElement("a");

                        downloadLink.href = dataUrl.replace("image/png", "image/octet-stream");
                        downloadLink.download = options.filename + '.' + options.type;
                        win.document.body.appendChild(downloadLink);
                        downloadLink.click();
                        win.document.body.removeChild(downloadLink);
                    }

                    fulfill(dataUrl);
                });
            }, 'Tinebase/js/html2canvas');
    });
};

Ext.ux.screenshot.ux = function(win, options) {
    // display countdown
    var secondsLeft = Ext.isNumber(options.countdown) ? options.countdown : 3;
    if (secondsLeft) {
        Ext.getBody().mask(String.format('Taking Screenshot in {0} seconds', secondsLeft));
        options.countdown = --secondsLeft;
        return Ext.ux.screenshot.ux.defer(1000, win, [win, options]);
    }

    Ext.getBody().unmask();
    Ext.ux.screenshot.get(win, options);
};
