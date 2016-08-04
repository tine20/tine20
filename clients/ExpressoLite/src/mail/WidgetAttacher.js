/*!
 * Expresso Lite
 * Handles uploads for email attachments.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define([
    'common-js/jQuery',
    'common-js/App',
    'common-js/UploadFile',
    'mail/ThreadMail'
],
function($, App, UploadFile, ThreadMail) {
App.loadCss('mail/WidgetAttacher.css');
var WidgetAttacher = function(options) {
    var userOpts = $.extend({
        $elem: null // jQuery object for the target DIV
    }, options);

    var THIS = this;
    var $targetDiv = userOpts.$elem;
    var onContentChangeCB = $.noop; // user callback

    function _BuildDisplayName(fileObj) {
        var $disp = $('#Attacher_template > .Attacher_fileNameDisplay').clone();
        $disp.find('.Attacher_fileTitle').text(fileObj.name !== undefined ? fileObj.name : fileObj.filename);
        $disp.find('.Attacher_fileSize').text('('+ThreadMail.FormatBytes(fileObj.size)+')');
        return $disp;
    }

    THIS.getAll = function() {
        var ret = [];
        $targetDiv.children('.Attacher_unit').each(function(idx, div) {
            var attachmentObj = $(div).data('file'); // previously kept into DIV
            ret.push(attachmentObj);
        });
        return ret;
    };

    THIS.rebuildFromMsg = function(headline) {
        for (var i = 0; i < headline.attachments.length; ++i) {
            var file = headline.attachments[i];
            var $divSlot = $('#Attacher_template > .Attacher_unit').clone();
            $divSlot.find('.Attacher_pro').remove();
            $divSlot.find('.Attacher_text').empty().append(_BuildDisplayName(file));
            $divSlot.appendTo($targetDiv);
            $divSlot.data('file', { // keep attachment object into DIV
                name: file.filename,
                size: parseInt(file.size),
                type: file['content-type'],
                partId: file.partId
            });
        }
        if (headline.attachments.length) {
            onContentChangeCB(); // invoke user callback
        }
        return THIS;
    };

    THIS.newAttachment = function() {
        var $divSlot = null;
        var tempFiles = [];

        var up = new UploadFile({
            url: App.getAjaxUrl() + '?r=uploadTempFile',
            chunkSize: 1024 * 200 // file sliced into 200 KB chunks
        });
        up.onProgress(function(pct, xhr) {
            if ($divSlot === null) { // first call, create DIV entry with progress bar and stuff
                $divSlot = $('#Attacher_template > .Attacher_unit').clone();
                $divSlot.appendTo($targetDiv);
                onContentChangeCB(); // invoke user callback
            }
            tempFiles.push(xhr.responseJSON.tempFile); // object returned by Tinebase.uploadTempFile
            $divSlot.find('.Attacher_text')
                .text('Carregando... '+(pct * 100).toFixed(0)+'%');
            $divSlot.find('.Attacher_pro')[0].value = pct;
        }).onDone(function(file, xhr) {
            if ($divSlot === null) {
                $divSlot = $('#Attacher_template > .Attacher_unit').clone();
                $divSlot.appendTo($targetDiv);
                onContentChangeCB(); // invoke user callback
            }
            if (xhr.responseJSON.status === 'success') {
                App.post('joinTempFiles', { tempFiles:JSON.stringify(tempFiles) })
                .done(function(tmpf) {
                    $divSlot.data('file', { // keep attachment object into DIV
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        tempFile: tmpf
                    });
                    $divSlot.find('.Attacher_text').empty().append(_BuildDisplayName(file));
                    $divSlot.find('.Attacher_pro').remove();
                });
            } else {
                $divSlot.find('.Attacher_text').text('Erro no carregamento do anexo.');
            }
        }).onFail(function(xhr, str) {
            console.log(xhr);
            alert(str);
        });

        return THIS;
    };

    THIS.removeAll = function() {
        $targetDiv.children('.Attacher_unit').remove();
        return THIS;
    };

    THIS.onContentChange = function(callback) {
        onContentChangeCB = callback; // onContentChange()
        return THIS;
    };

    $targetDiv.on('click', '.Attacher_remove', function() {
        var $slot = $(this).closest('.Attacher_unit');
        $slot.remove();
        onContentChangeCB(); // invoke user callback
    });
};

WidgetAttacher.Load = function() {
    // Static method, since this class can be instantied ad-hoc.
    return App.loadTemplate('WidgetAttacher.html');
};

return WidgetAttacher;
});
