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
                var canvas = win.document.createElement("canvas");
                canvas.width = win.innerWidth;
                canvas.height = win.innerHeight;

                Ext.ux.screenshot.setDPI(canvas, options.dpi);

                // Ext.getBody().addClass('x-html2canvas');

                html2canvas(win.document.body, {
                    canvas: canvas,
                    grabMouse: options.grabMouse,
                }).then(function(canvas) {

                    // Ext.getBody().removeClass('x-html2canvas');
                    
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

Ext.ux.screenshot.setDPI = function (canvas, dpi) {
    // Set up CSS size if it's not set up already
    if (!canvas.style.width)
        canvas.style.width = canvas.width + 'px';
    if (!canvas.style.height)
        canvas.style.height = canvas.height + 'px';

    var scaleFactor = dpi / 96;
    canvas.width = Math.ceil(canvas.width * scaleFactor);
    canvas.height = Math.ceil(canvas.height * scaleFactor);
    var ctx = canvas.getContext('2d');
    ctx.scale(scaleFactor, scaleFactor);
};