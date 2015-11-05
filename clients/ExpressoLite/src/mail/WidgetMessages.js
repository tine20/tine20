/*!
 * Expresso Lite
 * Widget to render the message bodies.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/DateFormat',
    'common-js/ContextMenu',
    'common-js/Contacts',
    'mail/ThreadMail'
],
function($, App, DateFormat, ContextMenu, Contacts, ThreadMail) {
App.LoadCss('mail/WidgetMessages.css');
return function(options) {
    var userOpts = $.extend({
        $elem: null, // jQuery object for the target DIV
        folderCache: [],
        wndCompose: null, // WidgetCompose object
        genericMugshot: '../img/person-generic.gif'
    }, options);

    var THIS         = this;
    var $targetDiv   = userOpts.$elem; // shorthand
    var curFolder    = null; // folder object currently loaded
    var menu         = null; // context menu object
    var onViewCB     = null; // user callbacks
    var onMarkReadCB = null;
    var onMoveCB     = null;

    function _EnlargeMugshot($img, bEnlarge) {
        var defer = $.Deferred();
        var src = $img.attr('src');
        if (src.substr(0, 5) === 'data:') { // apply effect only to real pictures
            if (bEnlarge) {
                $img.css('box-shadow', '3px 3px 3px #888')
                    .animate({ width:'90px' }, { duration:70, queue:false, complete:function() {
                        defer.resolve();
                    } });
            } else {
                $img.animate({ width:'20px' }, { duration:70, queue:false, complete:function() {
                    $img.css('box-shadow', '');
                    defer.resolve();
                } });
            }
        }
        return defer.promise();
    }

    function _FormatAttachments(attachs) {
        var ret = '';
        if (attachs !== undefined && attachs.length) {
            ret = '<b>'+(attachs.length === 1 ? 'Anexo' : attachs.length+' anexos')+'</b>: ';
            for (var i = 0; i < attachs.length; ++i) {
                ret += '<span style="white-space:nowrap;"><a href="#">'+attachs[i].filename+'</a> ' +
                    '('+ThreadMail.FormatBytes(attachs[i].size)+')</span>, ';
            }
            ret = ret.substr(0, ret.length - 2); // remove last comma
        }
        return ret;
    }

    function _FormatManyAddresses(addrs) {
        if (!addrs.length) return '<i>(ninguém)</i>';
        var ret = [];
        for (var i = 0; i < addrs.length; ++i) {
            var $span = $('#Messages_template .Messages_addrPerson').clone();
            var ad = addrs[i].toLowerCase();
            ret.push($span.find('.Messages_addrName').text(ad.substr(0, ad.indexOf('@'))).clone());
            ret.push($span.find('.Messages_addrDomain').text(ad.substr(ad.indexOf('@'))).clone())
            if (i !== addrs.length - 1) ret.push(', ');
        }
        return ret;
    }

    function _BuildDropdownMenu($divUnit) {
        var menu = new ContextMenu({ $btn:$divUnit.find('.Messages_dropdown') });
        menu.addOption('Marcar como não lida', function() { _MarkRead($divUnit, false); })
            .addOption('Responder', function() { _NewMail($divUnit, 're'); })
            .addOption('Responder a todos', function() { _NewMail($divUnit, 'reAll'); })
            .addOption('Encaminhar', function() { _NewMail($divUnit, 'fwd'); })
            .addOption('Apagar', function() { _DeleteMessage($divUnit); })
            .addHeader('Mover para...');

        var MenuRenderFolderLevel = function(folders, level) {
            $.each(folders, function(idx, folder) {
                if (folder.globalName !== curFolder.globalName) { // avoid move to current folder
                    menu.addOption(folder.localName, function() {
                        _MoveMessage($divUnit, folder); // pass folder object
                    }, level); // indentation
                }
                MenuRenderFolderLevel(folder.subfolders, level + 1);
            });
        };
        MenuRenderFolderLevel(userOpts.folderCache, 0);
    }

    function _BuildIcons(headline) {
        var icons = [];
        if (headline.replied)       icons.push($('#icons .icoReplied').clone());
        if (headline.wantConfirm)   icons.push($('#icons .icoConfirm').clone());
        if (headline.important)     icons.push($('#icons .icoImportant').clone());
        if (headline.signed)        icons.push($('#icons .icoSigned').clone());
        if (headline.hasAttachment) icons.push($('#icons .icoAttach').clone());
        if (headline.forwarded)     icons.push($('#icons .icoForwarded').clone());
        return icons;
    }

    function _BuildDivMail(headline) {
        var mugshot = Contacts.getMugshotSrc(headline.from.email);
        if (mugshot === '')
            mugshot = userOpts.genericMugshot;

        var unreadClass = headline.unread ? 'Messages_unread' : 'Messages_read';

        var $div = $('#Messages_template .Messages_unit').clone();
        $div.find('.Messages_top1').addClass(unreadClass);
        $div.find('.Messages_mugshot > img').attr('src', mugshot);
        $div.find('.Messages_fromName').text(headline.from.name);
        $div.find('.Messages_fromMail').text('('+headline.from.email+')');
        $div.find('.Messages_icons').append(_BuildIcons(headline));
        $div.find('.Messages_when').text(DateFormat.Long(headline.received));
        $div.find('.Messages_top2').addClass(unreadClass);
        $div.find('.Messages_addrTo').append(_FormatManyAddresses(headline.to));
        headline.cc.length ?
            $div.find('.Messages_addrCc').append(_FormatManyAddresses(headline.cc)) :
            $div.find('.Messages_addrCc').parent().remove();
        headline.bcc.length ?
            $div.find('.Messages_addrBcc').append(_FormatManyAddresses(headline.bcc)) :
            $div.find('.Messages_addrBcc').parent().remove();

        _BuildDropdownMenu($div);

        $div.data('headline', headline); // keep object
        return $div;
    }

    function _NewMail($div, action) {
        if (!$div.hasClass('Messages_unit'))
            $div = $div.closest('.Messages_unit');
        if (!$div.children('.Messages_content').is(':visible')) {
            window.alert('Abra a mensagem antes de '+(action==='fwd'?'encaminhá':'respondê')+'-la.');
        } else {
            var opts = { curFolder:curFolder };

            if (action === 'fwd') opts.forward = $div.data('headline');
            else if (action === 're') opts.reply = $div.data('headline');
            else if (action === 'reAll') opts.replyToAll = $div.data('headline');

            userOpts.wndCompose.show(opts);
        }
    }

    function _MarkRead($elem, asRead) {
        if (!$elem.hasClass('Messages_unit')) {
            $elem = $elem.closest('.Messages_unit');
        }
        var headline = $elem.data('headline');

        if ( (asRead && !headline.unread) || (!asRead && headline.unread) ) {
            window.alert('Mensagem já marcada como '+(asRead?'':'não')+' lida.');
        } else {
            $elem.find('.Messages_from:first').append(
                $(document.createElement('span'))
                    .addClass('Messages_throbber')
                    .append('&nbsp; ')
                    .append($('#icons .throbber').clone()) );

            if (!asRead && $elem.find('.Messages_content').is(':visible'))
                    $elem.children('.Messages_top1').trigger('click'); // collapse if expanded

            App.Post('markAsRead', { asRead:(asRead?1:0), ids:headline.id })
            .always(function() { $elem.find('.Messages_throbber').remove(); })
            .fail(function(resp) {
                window.alert('Erro ao alterar o flag de leitura das mensagens.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function() {
                headline.unread = !headline.unread; // update cache
                if (curFolder.id !== null) { // if not a search result
                    asRead ? --curFolder.unreadMails : ++curFolder.unreadMails;
                } else {
                    curFolder.searchedFolder.messages.length = 0; // force cache rebuild
                    curFolder.searchedFolder.threads.length = 0;
                }
                $elem.children('.Messages_top1,.Messages_top2')
                    .toggleClass('Messages_read', asRead).toggleClass('.Messages_unread', !asRead);
                if (onMarkReadCB !== null) {
                    onMarkReadCB(curFolder, headline); // invoke user callback
                }
            });
        }
    }

    function _MoveMessage($elem, destFolder) {
        if (!$elem.hasClass('Messages_unit'))
            $elem = $elem.closest('.Messages_unit');
        if ($elem.find('.throbber').length) // already working?
            return;
        var headline = $elem.data('headline');

        function ProceedMoving() {
            $elem.find('.Messages_fromName').hide();
            $elem.find('.Messages_fromMail').html(' &nbsp; <i>Movendo para '+destFolder.localName+'...</i>');
            $elem.find('.Messages_from').append($('#icons .throbber').clone());
            $elem.children('.Messages_top2,.Messages_attachs,.Messages_content').remove(); // won't expand anymore

            App.Post('moveMessages', { messages:headline.id, folder:destFolder.id })
            .always(function() { $elem.find('.throbber').remove(); })
            .fail(function(resp) {
                window.alert('Erro ao mover mensagem.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function() {
                $elem.slideUp(200, function() {
                    $elem.remove();
                    var origThread = ThreadMail.FindThread(curFolder.threads, headline);
                    --curFolder.totalMails; // update cache
                    ++destFolder.totalMails;
                    if (headline.unread) {
                        if (curFolder.id !== null) --curFolder.unreadMails;
                        ++destFolder.unreadMails;
                    }
                    ThreadMail.RemoveHeadlinesFromFolder([ headline.id ], curFolder);
                    if (curFolder.id === null) { // if a search result
                        curFolder.searchedFolder.messages.length = 0; // force cache rebuild
                        curFolder.searchedFolder.threads.length = 0;
                    } else {
                        destFolder.messages.length = 0; // force cache rebuild
                        destFolder.threads.length = 0;
                    }
                    if (onMoveCB !== null) {
                        onMoveCB(destFolder, origThread);
                    }
                });
            });
        }

        $elem.find('.Messages_top2').is(':visible') ?
            $elem.find('.Messages_top1').trigger('click', ProceedMoving) : // if expanded, collapse
            ProceedMoving();
    }

    function _DeleteMessage($elem) {
        if (!$elem.hasClass('Messages_unit')) {
            $elem = $elem.closest('.Messages_unit');
        }
        if ($elem.find('.throbber').length) { // already working?
            return;
        }
        var headline = $elem.data('headline');

        if (curFolder.globalName !== 'INBOX/Trash') { // just move to trash folder
            _MoveMessage($elem, ThreadMail.FindFolderByGlobalName('INBOX/Trash', userOpts.folderCache));
        } else if (window.confirm('Deseja apagar esta mensagem?')) { // we're in trash folder, add deleted flag
            function ProceedDeleting() {
                $elem.find('.Messages_fromName').hide();
                $elem.find('.Messages_fromMail').html(' &nbsp; <i>Excluindo...</i>');
                $elem.find('.Messages_from').append($('#icons .throbber').clone());
                $elem.children('.Messages_top2,.Messages_attachs,.Messages_content').remove(); // won't expand anymore

                App.Post('deleteMessages', { messages:headline.id, forever:1 })
                .always(function() { $elem.find('.throbber').remove(); })
                .fail(function(resp) {
                    window.alert('Erro ao apagar email.\n' +
                        'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                }).done(function(status) {
                    $elem.slideUp(200, function() {
                        $elem.remove();
                        var origThread = ThreadMail.FindThread(curFolder.threads, headline);
                        --curFolder.totalMails; // update cache
                        if (headline.unread && curFolder.id !== null) {
                            --curFolder.unreadMails;
                        }
                        ThreadMail.RemoveHeadlinesFromFolder([ headline.id ], curFolder);
                        if (curFolder.id === null) { // if a search result
                            curFolder.searchedFolder.messages.length = 0; // force cache rebuild
                            curFolder.searchedFolder.threads.length = 0;
                        }
                        if (onMoveCB !== null) {
                            onMoveCB(null, origThread);
                        }
                    });
                });
            }

            $elem.find('.Messages_top2').is(':visible') ?
                $elem.find('.Messages_top1').trigger('click', ProceedDeleting) : // if expanded, collapse
                ProceedDeleting();
        }
    }

    function _LoadMugshots(thread, unitDivs, onDone) {
        var mugshotAddrs = []; // email addresses to have mugshot fetched
        for (var i = 0; i < thread.length; ++i) { // each thread is an array of headlines
            var fromAddr = thread[i].from.email.toLowerCase();
            if (fromAddr.indexOf('@serpro.gov.br') !== -1 && mugshotAddrs.indexOf(fromAddr) === -1) { // only @serpro addresses
                mugshotAddrs.push(fromAddr);
            }
        }

        Contacts.loadMugshots(mugshotAddrs).done(function() {
            for (var i = 0; i < unitDivs.length; ++i) {
                if (!unitDivs[i].closest('body').length) // not in DOM anymore
                    continue;
                if (unitDivs[i].find('.Messages_mugshot > img').attr('src') === userOpts.genericMugshot) {
                    var imgsrc = Contacts.getMugshotSrc(unitDivs[i].data('headline').from.email);
                    if (imgsrc !== '') {
                        unitDivs[i].find('.Messages_mugshot > img')
                            .attr('src', imgsrc)
                            .hide()
                            .fadeIn(500);
                    }
                }
            }
            if (onDone !== undefined) onDone();
        });
    }

    THIS.load = function() {
        return App.LoadTemplate('WidgetMessages.html');
    };

    THIS.empty = function() {
        $targetDiv.children('.Messages_unit').remove();
        return THIS;
    };

    THIS.render = function(thread, currentFolder) {
        curFolder = currentFolder; // keep
        $targetDiv.children('.Messages_unit').remove(); // clear, if any
        var divs = [];
        var firstUnread = -1;
        thread.reverse(); // headlines now sorted oldest first
        for (var i = 0; i < thread.length; ++i) { // each thread is an array of headlines
            divs.push(_BuildDivMail(thread[i]));
            if (firstUnread === -1 && thread[i].unread) {
                firstUnread = i;
            }
        }
        thread.reverse(); // headlines now sorted newest first again
        $targetDiv.append(divs);
        firstUnread = (firstUnread !== -1) ? firstUnread : thread.length - 1; // open 1st unread, or last
        $targetDiv.find('.Messages_top1:eq('+firstUnread+')').trigger('click', function() {
            _LoadMugshots(thread, divs);
        });
        return THIS;
    };

    THIS.redrawIcons = function(headline) {
        $targetDiv.children('.Messages_unit').each(function(idx, elem) {
            var $div = $(elem);
            if ($div.data('headline').id === headline.id) {
                $div.find('.Messages_icons').html(_BuildIcons(headline));
            }
        });
        return THIS;
    };

    THIS.count = function() {
        return $targetDiv.find('div.Messages_unit').length;
    };

    THIS.countOpen = function() {
        return $targetDiv.find('div.Messages_body:visible').length;
    };

    THIS.closeAll = function() {
        $targetDiv.find('div.Messages_content:visible')
            .prevAll('div.Messages_top1').trigger('click');
        return THIS;
    };

    THIS.onView = function(callback) {
        onViewCB = callback;
        return THIS;
    };

    THIS.onMarkRead = function(callback) {
        onMarkReadCB = callback;
        return THIS;
    };

    THIS.onMove = function(callback) {
        onMoveCB = callback;
        return THIS;
    };

    $targetDiv.on('click', '.Messages_top1,.Messages_top2', function(ev, onDone) { // open message
        var $divUnit = $(this).closest('.Messages_unit');
        var headline = $divUnit.data('headline');
        if (!$divUnit.find('.Messages_top2').is(':visible')) { // will expand

            function PutContentsAndSlideDown() {
                $divUnit.find('.Messages_attachs').html(_FormatAttachments(headline.attachments));
                $divUnit.find('.Messages_body').html(headline.body.message);
                if (headline.body.quoted !== null) {
                    $divUnit.find('.Messages_showQuote').show();
                    $divUnit.find('.Messages_quote').html(headline.body.quoted);
                }
                var toSlide = headline.attachments.length ?
                    '.Messages_top2,.Messages_attachs,.Messages_content' :
                    '.Messages_top2,.Messages_content';
                $divUnit.find(toSlide).slideDown(200).promise('fx').done(function() {
                    $divUnit.children('.Messages_top1,.Messages_top2')
                        .removeClass('Messages_unread').addClass('Messages_read');

                    if (headline.unread) {
                        _MarkRead($divUnit, true);
                    }
                    if (onViewCB !== null) {
                        onViewCB(curFolder, headline); // invoke user callback
                    }
                    if (onDone !== undefined) {
                        onDone();
                    }
                });
            }

            if (headline.body === null) { // not cached yet
                $divUnit.append(
                    $('#Messages_template .Messages_loading').clone()
                        .append('Carregando mensagem... ')
                        .append($('#icons .throbber').clone())
                );

                App.Post('getMessage', {
                    id: headline.id,
                    ajaxUrl: App.GetAjaxUrl()
                }).always(function() {
                    $divUnit.find('.Messages_loading').remove();
                }).fail(function(resp) {
                    window.alert('Erro ao carregar email.\n' +
                        'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                }).done(function(msg) {
                    headline.attachments = msg.attachments; // cache
                    headline.body = msg.body;
                    PutContentsAndSlideDown();
                });
            } else { // already cached
                PutContentsAndSlideDown();
            }
        } else { // will collapse
            $divUnit.find('.Messages_quote').hide();
            var toGo = (headline.attachments !== null && headline.attachments.length) ?
                '.Messages_top2,.Messages_attachs,.Messages_content' :
                '.Messages_top2,.Messages_content';
            $divUnit.find(toGo).slideUp(200).promise('fx').done(function() {
                if (onDone !== undefined) {
                    onDone();
                }
            });
        }
        return false;
    });

    $targetDiv.on('click', '.Messages_attachs a', function() { // click attachment
        var $lnk = $(this);
        $lnk.blur();
        var idx = $lnk.parent('span').index(); // child index; 0 is "<b>Anexo</b>", others are the link spans
        var headline = $lnk.closest('.Messages_unit').data('headline');
        var attach = headline.attachments[idx - 1];
        window.open(App.GetAjaxUrl() +
            '?r=downloadAttachment&' +
            'fileName='+encodeURIComponent(attach.filename)+'&' +
            'messageId='+headline.id+'&' +
            'partId='+attach.partId,
            '_blank'); // usually will open another tab on the browser
        return false;
    });

    $targetDiv.on('click', '.Messages_showQuote', function() {
        $(this).next('.Messages_quote').slideToggle(200);
    });

    $targetDiv.on('mouseenter', '.Messages_mugshot > img', function() {
        if (!App.IsPhone()) {
            _EnlargeMugshot($(this), true);
        }
    }).on('mouseleave', '.Messages_mugshot > img', function() {
        if (!App.IsPhone()) {
            _EnlargeMugshot($(this), false);
        }
    });

    $targetDiv.on('click', '.Messages_mugshot > img', function(ev) {
        if (App.IsPhone()) {
            ev.stopImmediatePropagation();
            var $img = $(this);
            _EnlargeMugshot($img, true).done(function() {
                window.setTimeout(function() {
                    _EnlargeMugshot($img, false);
                }, 1250);
            });
        }
    });
};
});
