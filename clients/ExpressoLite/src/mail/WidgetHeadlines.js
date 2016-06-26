/*!
 * Expresso Lite
 * Widget to render the email headlines.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/DateFormat',
    'common-js/Contacts',
    'mail/ThreadMail'
],
function($, App, DateFormat, Contacts, ThreadMail) {
App.LoadCss('mail/WidgetHeadlines.css');
return function(options) {
    var userOpts = $.extend({
        $elem: null,    // jQuery object for the target DIV
        folderCache: [] // central unique cache of all folders and messages
    }, options);

    var THIS            = this;
    var $targetDiv      = userOpts.$elem; // shorthand
    var curFolder       = null; // folder object currently loaded
    var menu            = null; // context menu object
    var onClickCB       = $.noop; // user callbacks
    var onCheckCB       = $.noop;
    var onMarkReadCB    = $.noop;
    var onMoveCB        = $.noop;
    var $prevClick      = null; // used in checkbox click event, modified by buildContextMenu()
    var lastCheckWith   = null; // 'leftClick' || 'rightClick', used in buildContextMenu()

    function _CreateDivLoading(msg) {
        var $div = $('#Headlines_template .Headlines_loading').clone();
        if (msg !== null) $div.append(msg);
        $div.append([ ' ', $('#icons .throbber').clone() ]);
        return $div;
    }

    function _GetFolderLocalNameByGlobalName(globalName) {
        var ln = null;
        (function getLN(folders) {
            for (var i = 0; i < folders.length; ++i) {
                if (folders[i].globalName === globalName) {
                    ln = folders[i].localName;
                    break;
                } else {
                    getLN(folders[i].subfolders);
                }
            }
        })(userOpts.folderCache);
        return ln;
    }

    function _FindHeadlineDiv(headline) {
        var $retDiv = null; // given a headline object, find the DIV to which it belongs
        $targetDiv.children('div').each(function(idx, div) {
            var $div = $(div);
            var thread = $div.data('thread'); // each thread is an array of headlines
            for (var i = 0; i < thread.length; ++i) {
                if (thread[i].id === headline.id) {
                    $retDiv = $div;
                    return false; // break
                }
            }
        });
        return $retDiv;
    }

    function _RemoveDuplicates(arr) {
        var ret = [];
        for (var a = 0; a < arr.length; ++a) { // faster than simply using indexOf
            var exists = false;
            for (var r = 0; r < ret.length; ++r) {
                if (arr[a] === ret[r]) {
                    exists = true;
                    break;
                }
            }
            if (!exists) ret.push(arr[a]);
        }
        return ret;
    }

    function _BuildSendersText(thread, isSentFolder) {
        var names = { read:[], unread:[] };
        for (var i = 0; i < thread.length; ++i) { // a thread is an array of headlines
            var msg = thread[i];
            var who = msg.unread ? names.unread : names.read;
            if (!isSentFolder) {
                who.push( msg.from.name.indexOf('@') !== -1 ? // an actual email address
                    Contacts.humanizeLogin(msg.from.name, false) : msg.from.name );
            } else { // on Sent folder, show "to" field instead of "from"
                for (var t = 0; t < msg.to.length; ++t) {
                    who.push(Contacts.humanizeLogin(msg.to[t], msg.to.length > 1));
                }
            }
        }
        names.read.reverse(); // senders in chronological order, originally newest first
        names.unread.reverse();
        names.read = _RemoveDuplicates(names.read);
        names.unread = _RemoveDuplicates(names.unread);

        if (names.read.length + names.unread.length > 1) {
            for (var i = 0; i < names.read.length; ++i) {
                names.read[i] = names.read[i].split(' ')[0]; // many people, remove surnames
            }
            for (var i = 0; i < names.unread.length; ++i) {
                names.unread[i] = names.unread[i].split(' ')[0]; // unread also in bold
            }
        }
        for (var i = 0; i < names.unread.length; ++i) {
            names.unread[i] = '<b>'+names.unread[i]+'</b>'; // unread in bold
        }

        if (!names.read.length && !names.unread.length) {
            return '(ninguém)';
        } else {
            return (names.read.length ? names.read.join(', ') : '') +
                (names.read.length && names.unread.length ? ', ' : '') +
                (names.unread.length ? names.unread.join(', ') : '') +
                (thread.length > 1 ? ' ('+thread.length+')' : '');
        }
    }

    function _BuildDiv(thread, isSentFolder) {
        var hasHighlight  = false;
        var hasReplied    = false;
        var hasForwarded  = false;
        var hasAttachment = false;
        var hasImportant  = false;
        var hasUnread     = false;
        var hasSigned     = false;
        var wantConfirm   = false;
        for (var i = 0; i < thread.length; ++i) { // a thread is an array of headlines
            var msg = thread[i];
            if (msg.flagged)       hasHighlight = true; // at least 1 email in the thread has highlight status
            if (msg.hasAttachment) hasAttachment = true;
            if (msg.important)     hasImportant = true;
            if (msg.unread)        hasUnread = true;
            if (msg.signed)        hasSigned = true;
            if (msg.replied)       hasReplied = true;
            if (msg.forwarded)     hasForwarded = true
            if (msg.unread && msg.wantConfirm) wantConfirm = true; // confirmation only if unread
        }

        var unreadClass = hasUnread ? 'Headlines_entryUnread' : 'Headlines_entryRead';
        var $elemHl = $('#icons ' + (hasHighlight ? '.icoHigh1' : '.icoHigh0')).clone();
        var $div = $('#Headlines_template .Headlines_entry').clone();
        $div.addClass(unreadClass);
        $div.find('.Headlines_sender').html(_BuildSendersText(thread, isSentFolder));
        $div.find('.Headlines_highlight').append($elemHl);
        $div.find('.Headlines_subject').text(thread[thread.length-1].subject != '' ?
            thread[thread.length-1].subject : '(sem assunto)');

        var icons = [];
        if (hasReplied)    icons.push($('#icons .icoReplied').clone());
        if (wantConfirm)   icons.push($('#icons .icoConfirm').clone());
        if (hasImportant)  icons.push($('#icons .icoImportant').clone());
        if (hasSigned)     icons.push($('#icons .icoSigned').clone());
        if (hasAttachment) icons.push($('#icons .icoAttach').clone());
        if (hasForwarded)  icons.push($('#icons .icoForwarded').clone());
        $div.find('.Headlines_icons').append(icons);

        $div.find('.Headlines_when').text(DateFormat.Humanize(thread[0].received));
        $div.data('thread', thread); // keep thread object within DIV
        return $div;
    }

    function _BuildAllThreadsDivs(threads) {
        var divs = [];
        for (var i = 0; i < curFolder.threads.length; ++i) {
            divs.push(_BuildDiv(curFolder.threads[i], curFolder.globalName === 'INBOX/Sent'));
        }
        return divs;
    }

    function _AnimateFirstHeadlines($divs, $loading) {
        var defer = $.Deferred();
        window.setTimeout(function() {
            $loading.animate({
                'margin-top': ($targetDiv.height() - 20)+'px'
            }, 200, function() {
                $loading.remove();
                $targetDiv.css('height', ''); // restore
                var iLast = -1; // index of last visible DIV
                var cyMax = $(window).height();
                for (var i = 0; i < $divs.length; ++i) {
                    $targetDiv.append($divs[i]);
                    if ($divs[i].offset().top > cyMax) {
                        iLast = i;
                        break;
                    }
                }
                $targetDiv.css('display', 'none').fadeIn(150, function() {
                    $targetDiv.css('height', '')
                        .append($divs.slice(iLast + 1)); // append remaning
                    defer.resolve(); // finally resolve deferred
                });
            });
        }, 50);
        return defer.promise();
    }

    function _RedrawDiv($div) {
        var $newDiv = _BuildDiv($div.data('thread'), curFolder.globalName === 'INBOX/Sent');
        //~ if (!$div.children('.Headlines_check').is(':visible'))
            //~ $newDiv.find('.Headlines_check').hide();
        if ($div.hasClass('Headlines_entryCurrent')) {
            $newDiv.addClass('Headlines_entryCurrent');
        }
        var isChecked = $div.hasClass('Headlines_entryChecked');
        $div.replaceWith($newDiv);
        if (isChecked) {
            $newDiv.find('.Headlines_check > div').trigger('click');
        }
    }

    function _FetchDraftMessage($div, headline, onDone) {
        if (headline.body === null) { // message content not fetched yet
            var $check = $div.find('.Headlines_check > [class^=icoCheck]').clone(); // keep

            $div.find('.Headlines_check > [class^=icoCheck]').replaceWith(
                $('#icons .throbber').clone().css('padding', '8px') ); // replace checkbox with throbber

            App.Post('getMessage', {
                id: headline.id,
                ajaxUrl: App.GetAjaxUrl()
            }).always(function() {
                $div.find('.throbber').replaceWith($check); // restore checkbox
            }).fail(function(resp) {
                window.alert('Erro ao carregar email.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function(msg) {
                headline.attachments = msg.attachments; // cache
                headline.body = msg.body;
                if (onDone !== undefined) onDone();
            });
        } else {
            if (onDone !== undefined) onDone();
        }
    }

    THIS.load = function() {
        return App.LoadTemplate('WidgetHeadlines.html');
    };

    THIS.markRead = function(asRead) {
        var relevantHeadlines = []; // headlines to have their flag actually changed
        var $checkedDivs = $targetDiv.find('.Headlines_entryChecked');

        $checkedDivs.each(function(idx, elem) {
            var $div = $(elem);
            var thread = $div.data('thread'); // a thread is an array of messages
            for (var i = 0; i < thread.length; ++i) {
                if ((asRead && thread[i].unread) || (!asRead && !thread[i].unread)) {
                    thread[i].unread = !thread[i].unread; // update cache
                    if (curFolder.id !== null) { // if not a search result
                        asRead ? --curFolder.unreadMails : ++curFolder.unreadMails;
                    }
                    relevantHeadlines.push(thread[i]);
                }
            }
        });

        if (!relevantHeadlines.length) {
            window.alert('Nenhuma mensagem a ser marcada como '+(asRead?'':'não')+' lida.');
        } else {
            var $check = $checkedDivs.find('.icoCheck1:first').clone(); // keep
            $checkedDivs.find('.icoCheck1').replaceWith(
                $('#icons .throbber').clone().css('padding', '8px') ); // replace checkbox with throbber
            var relevantIds = $.map(relevantHeadlines, function(elem) { return elem.id; });

            App.Post('markAsRead', { asRead:(asRead?1:0), ids:relevantIds.join(',') })
            .always(function() {
                $checkedDivs.find('.throbber').replaceWith($check); // restore checkbox
                THIS.clearChecked();
                onMarkReadCB(curFolder);
            }).fail(function(resp) {
                window.alert('Erro ao alterar o flag de leitura das mensagens.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function() {
                if (curFolder.searchedFolder !== undefined) { // if a search result
                    curFolder.searchedFolder.messages.length = 0; // force cache rebuild
                    curFolder.searchedFolder.threads.length = 0;
                }
                $checkedDivs.each(function(idx, elem) {
                    _RedrawDiv($(elem));
                });
            });
        }
        return THIS;
    };

    THIS.moveMessages = function(destFolder) {
        var $checkedDivs = $targetDiv.find('.Headlines_entryChecked');
        if ($checkedDivs.find('.throbber').length) { // already working?
            return;
        }
        var headlines = []; // will hold all individual headlines
        $checkedDivs.each(function(idx, elem) { // each selected row
            var thread = $(elem).data('thread'); // thread to be moved, a thread is an array of headlines
            headlines.push.apply(headlines, thread);
            $(elem).children('.Headlines_sender')
                .empty()
                .append($('#icons .throbber').clone())
                .append('&nbsp; <i>Movendo para '+destFolder.localName+'...</i>');

            curFolder.totalMails -= thread.length; // update cache
            destFolder.totalMails += thread.length;
            for (var i = 0; i < thread.length; ++i) {
                if (thread[i].unread) {
                    if (curFolder.id !== null) --curFolder.unreadMails;
                    ++destFolder.unreadMails;
                }
            }
        });
        var msgIds = $.map(headlines, function(elem) { return elem.id; });
        ThreadMail.RemoveHeadlinesFromFolder(msgIds, curFolder);
        destFolder.messages.length = 0; // force cache rebuild
        destFolder.threads.length = 0;

        App.Post('moveMessages', { messages:msgIds.join(','), folder:destFolder.id })
        .fail(function(resp) {
            window.alert('Erro ao mover email.\n' +
                'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
        }).done(function() {
            if (curFolder.searchedFolder !== undefined) { // if a search result
                curFolder.searchedFolder.messages.length = 0; // force cache rebuild
                curFolder.searchedFolder.threads.length = 0;
            }
            $checkedDivs.slideUp(200).promise('fx').done(function() {
                $checkedDivs.remove();
                onMoveCB(destFolder);
            });
        });
        return THIS;
    };

    THIS.toggleStarred = function() {
        var $checkedDivs = $targetDiv.find('.Headlines_entryChecked');
        var headlines = [];
        var willStar = false;

        $checkedDivs.each(function(idx, elem) {
            var $div = $(elem);
            var thread = $div.data('thread');
            for (var i = 0; i < thread.length; ++i) {
                headlines.push(thread[i]);
                if (!thread[i].flagged) {
                    willStar = true; // at least 1 unstarred? star all
                }
            }
        });
        var msgIds = $.map(headlines, function(elem) { return elem.id; });
        $checkedDivs.find('div[class^=icoHigh]').replaceWith(_CreateDivLoading(null).css('margin', '0'));
        for (var i = 0; i < headlines.length; ++i) {
            headlines[i].flagged = willStar; // update cache
        }

        App.Post('markAsHighlighted', { ids:msgIds.join(','), asHighlighted:(willStar?'1':'0') })
        .always(function() {
            $checkedDivs.find('.Headlines_loading').replaceWith(
                (willStar ? $('#icons .icoHigh1') : $('#icons .icoHigh0')).clone() );
        }).fail(function(resp) {
            window.alert('Erro ao alterar o flag de destaque das mensagens.\n' +
                'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
        }).done(function() {
            THIS.clearChecked();
        });
        return THIS;
    };

    THIS.deleteMessages = function() {
        if (curFolder.globalName !== 'INBOX/Trash') { // just move to trash folder
            THIS.moveMessages(ThreadMail.FindFolderByGlobalName('INBOX/Trash', userOpts.folderCache));
        } else if (window.confirm('Deseja apagar as mensagens selecionadas?')) { // we're in trash folder, add deleted flag
            var $checkedDivs = $targetDiv.find('.Headlines_entryChecked');
            if ($checkedDivs.find('.throbber').length) { // already working?
                return;
            }
            var headlines = [];
            $checkedDivs.each(function(idx, elem) {
                var thread = $(elem).data('thread');
                headlines.push.apply(headlines, thread);
                $(elem).children('.Headlines_sender')
                    .empty()
                    .append($('#icons .throbber').clone())
                    .append('&nbsp; <i>Excluindo...</i>');

                curFolder.totalMails -= thread.length; // update cache
                if (curFolder.id !== null) { // if not a search result
                    for (var i = 0; i < thread.length; ++i) {
                        if (thread[i].unread && curFolder.id !== null) { // if not a search result
                            --curFolder.unreadMails;
                        }
                    }
                }
            });
            var msgIds = $.map(headlines, function(elem) { return elem.id; });
            ThreadMail.RemoveHeadlinesFromFolder(msgIds, curFolder);

            App.Post('deleteMessages', { messages:msgIds.join(','), forever:1 })
            .fail(function(resp) {
                window.alert('Erro ao apagar email.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function(status) {
                if (curFolder.searchedFolder !== undefined) { // if a search result
                    curFolder.searchedFolder.messages.length = 0; // force cache rebuild
                    curFolder.searchedFolder.threads.length = 0;
                }
                $checkedDivs.slideUp(200).promise('fx').done(function() {
                    $checkedDivs.remove();
                    onMoveCB(null);
                });
            });
        }
        return THIS;
    };

    THIS.loadFolder = function(folder, howMany) {
        var defer = $.Deferred();
        curFolder = folder; // cache
        $targetDiv.empty();

        if (!curFolder.totalMails) { // no messages on this folder
            return defer.resolve();
        } else {
            $targetDiv.css('height', '100%'); // important for _AnimateFirstHeadlines()
            var $loading = _CreateDivLoading('Carregando mensagens...').appendTo($targetDiv);
            if (!curFolder.messages.length) { // not cached yet
                App.Post('searchHeadlines', {
                    folderIds: curFolder.id,
                    start: 0,
                    limit: howMany
                }).fail(function(resp) {
                    window.alert('Erro na consulta dos emails de "'+curFolder.localName+'"\n'+resp.responseText);
                    $targetDiv.children('.Headlines_loading').remove();
                    defer.reject();
                }).done(function(headlines) {
                    curFolder.messages.length = 0;
                    curFolder.messages.push.apply(curFolder.messages, ThreadMail.ParseTimestamps(headlines));
                    curFolder.threads.length = 0;
                    curFolder.threads.push.apply(
                        curFolder.threads,
                        (curFolder.globalName === 'INBOX/Drafts') ?
                            ThreadMail.MakeThreadsWithSingleMessage(headlines) :
                            ThreadMail.MakeThreads(headlines) // in thread: oldest first
                    );
                    _AnimateFirstHeadlines(_BuildAllThreadsDivs(), $loading).done(function() {
                        defer.resolve();
                    });
                });
            } else {
                _AnimateFirstHeadlines(_BuildAllThreadsDivs(), $loading).done(function() {
                    defer.resolve();
                });
            }
        }
        return defer.promise();
    };

    THIS.searchMessages = function(text, howMany) {
        var defer = $.Deferred();
        $targetDiv.empty().css('height', '100%'); // important for _AnimateFirstHeadlines()
        var $loading = _CreateDivLoading('Buscando "'+text+'"...').appendTo($targetDiv);
        var isSearchAfterSearch = (curFolder.searchedFolder !== undefined); // this is a search made while another search is still active

        var theCurFolderId = isSearchAfterSearch ?
            curFolder.searchedFolder.id : curFolder.id;
        App.Post('searchHeadlines', {
            what: text,
            folderIds: theCurFolderId, // multiple folder IDs separated by commas
            start: 0,
            limit: howMany
        }).fail(function(resp) {
            if (resp.responseText.indexOf('please refine') !== -1) {
                $loading.remove();
                window.alert('A busca por "'+text+'" retornou muitos resultados.\n'+
                    'Pesquise por um termo mais específico.');
            } else {
                window.alert('Erro na busca por "'+text+'".\n'+resp.responseText);
            }
            defer.reject();
        }).done(function(resFolder) { // returns a virtual folder with search result, ID/globalName are null
            resFolder.searchedFolder = isSearchAfterSearch ? curFolder.searchedFolder : curFolder; // cache current folder being searched
            resFolder.searchedText = text; // cache the text being searched for eventual loadMore() calls
            resFolder.localName = 'Busca em '+(isSearchAfterSearch ? curFolder.searchedFolder : curFolder).localName; // will become page title

            curFolder = resFolder; // virtual folder with search result is our current folder now
            curFolder.messages = ThreadMail.ParseTimestamps(curFolder.messages);
            curFolder.threads.push.apply(curFolder.threads,
                ThreadMail.MakeThreadsWithSingleMessage(curFolder.messages));
            _AnimateFirstHeadlines(_BuildAllThreadsDivs(), $loading).done(function() {
                defer.resolve(curFolder); // return virtual search folder
            });
        });
        return defer.promise();
    };

    THIS.loadMore = function(howMany) {
        var defer = $.Deferred();
        var $divLoading = _CreateDivLoading('Carregando mensagens...')
        $divLoading.appendTo($targetDiv);

        var thisIsASearch = (curFolder.searchedFolder !== undefined); // actually a search result?

        App.Post('searchHeadlines', {
            what: thisIsASearch ? curFolder.searchedText : '',
            folderIds: thisIsASearch ? curFolder.searchedFolder.id : curFolder.id,
            start: curFolder.messages.length,
            limit: howMany
        }).always(function() { $divLoading.remove(); })
        .fail(function(resp) {
            window.alert('Erro ao trazer mais emails de "'+curFolder.localName+'"\n'+resp.responseText);
        }).done(function(mails2) {
            if (thisIsASearch) {
                mails2 = mails2.messages; // search returns more data than we need for loadMore()
            }
            ThreadMail.Merge(curFolder.messages, ThreadMail.ParseTimestamps(mails2)); // cache
            curFolder.threads.length = 0;
            curFolder.threads.push.apply( // rebuild
                curFolder.threads,
                (thisIsASearch || curFolder.globalName === 'INBOX/Drafts') ?
                    ThreadMail.MakeThreadsWithSingleMessage(curFolder.messages) :
                    ThreadMail.MakeThreads(curFolder.messages)
            );
            $targetDiv.empty().append(_BuildAllThreadsDivs());
            defer.resolve();
        });

        return defer.promise();
    };

    THIS.loadNew = function(howMany, onDone) {
        if (curFolder.searchedFolder !== undefined) { // actually a search result? do nothing
            return THIS;
        }

        var $divLoading = _CreateDivLoading('Carregando mensagens...');
        $targetDiv.prepend($divLoading);

        var headl0 = null;
        var $current = $targetDiv.find('.Headlines_entryCurrent');
        if ($current.length) {
            headl0 = $current.data('thread')[0]; // 1st headline of thread being read
        }

        App.Post('searchHeadlines', { folderIds:curFolder.id, start:0, limit:howMany })
        .always(function() { $divLoading.remove(); })
        .fail(function(resp) {
            window.alert('Erro na consulta dos emails de "'+curFolder.localName+'".\n' +
                'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
        }).done(function(headlines) {
            ThreadMail.Merge(curFolder.messages, ThreadMail.ParseTimestamps(headlines)); // insert into cache
            curFolder.threads = (curFolder.globalName === 'INBOX/Drafts') ?
                ThreadMail.MakeThreadsWithSingleMessage(curFolder.messages) :
                ThreadMail.MakeThreads(curFolder.messages); // in thread: oldest first
            $targetDiv.empty().append(_BuildAllThreadsDivs());
            if (headl0 !== null) {
                _FindHeadlineDiv(headl0).addClass('Headlines_entryCurrent');
            }
            if (onDone !== undefined && onDone !== null) {
                onDone(); // invoke user callback
            }
        });

        return THIS;
    };

    THIS.getCurrent = function() {
        var curThread = null;
        var $cur = $targetDiv.find('.Headlines_entryCurrent');
        if ($cur.length) {
            curThread = $cur.data('thread');
        }
        return curThread;
    };

    THIS.clearCurrent = function() {
        $targetDiv.find('.Headlines_entryCurrent').removeClass('Headlines_entryCurrent');
        return THIS;
    };

    THIS.getChecked = function() {
        var $checkeds = $targetDiv.find('.Headlines_entryChecked');
        var chThreads = [];
        $checkeds.each(function(idx, chDiv) {
            chThreads.push($(chDiv).data('thread')); // each thread is an array of headlines
        });
        return chThreads;
    };

    THIS.setCurrentChecked = function() {
        var $checkeds = $targetDiv.find('.Headlines_entryChecked');
        $checkeds.removeClass('Headlines_entryChecked');
        $checkeds.find('.icoCheck1').removeClass('icoCheck1').addClass('icoCheck0');

        var $cur = $targetDiv.find('.Headlines_entryCurrent');
        if ($cur.length) {
            $cur.addClass('Headlines_entryChecked');
            $cur.find('.icoCheck0').removeClass('icoCheck0').addClass('icoCheck1');
            onCheckCB(); // invoke user callback
        }
        return THIS;
    };

    THIS.clearChecked = function() {
        var $checkeds = $targetDiv.find('.Headlines_entryChecked');
        $checkeds.removeClass('Headlines_entryChecked');
        $checkeds.find('.icoCheck1').removeClass('icoCheck1').addClass('icoCheck0');
        onCheckCB(); // invoke user callback
        return THIS;
    };

    THIS.setCheckboxesVisible = function(isVisible) {
        $('.Headlines_check').css('display', isVisible ? '' : 'none');
        return THIS;
    };

    THIS.redraw = function(headline) {
        var $div = _FindHeadlineDiv(headline); // the DIV which contains the headline
        if ($div !== null) {
            _RedrawDiv($div);
        }
        return THIS;
    };

    THIS.redrawByThread = function(thread, onDone) {
        $targetDiv.children('div').each(function(idx, div) {
            var $div = $(div);
            if ($div.data('thread') === thread) { // compare references
                if (!thread.length) { // headline entry will be deleted
                    $div.slideUp(200, function() {
                        $div.remove();
                        if (onDone !== undefined && onDone !== null) {
                            onDone();
                        }
                    });
                } else { // headline entry will be just updated
                    var $newDiv = _BuildDiv($div.data('thread'), curFolder.globalName === 'INBOX/Sent');
                    if (!$div.children('.Headlines_check').is(':visible')) {
                        $newDiv.find('.Headlines_check').hide();
                    }
                    if ($div.hasClass('Headlines_entryCurrent')) {
                        $newDiv.addClass('Headlines_entryCurrent');
                    }
                    $div.replaceWith($newDiv);
                    if (onDone !== undefined && onDone !== null) {
                        onDone();
                    }
                }
                return false; // break
            }
        });
        return THIS;
    };

    THIS.calcScrollTopOf = function(thread) {
        var cy = 0;
        $targetDiv.children('div').each(function(idx, div) {
            var $div = $(div);
            var cyDiv = $div.outerHeight();
            if ($div.data('thread')[0].id === thread[0].id) {
                cy += cyDiv / 2;
                return false; // break
            }
            cy += cyDiv;
        });
        return cy;
    };

    THIS.onClick = function(callback) {
        onClickCB = callback; // onClick(thread)
        return THIS;
    };

    THIS.onCheck = function(callback) {
        onCheckCB = callback; // onCheck()
        return THIS;
    };

    THIS.onMarkRead = function(callback) {
        onMarkReadCB = callback; // onMarkRead(folder)
        return THIS;
    };

    THIS.onMove = function(callback) {
        onMoveCB = callback; // onMove(destFolder)
        return THIS;
    };

    $targetDiv.on('click', '.Headlines_entry', function(ev) { // click on headline
        if (!ev.shiftKey) {
            var $div = $(this);
            var thread = $div.data('thread');
            if (curFolder.globalName === 'INBOX/Drafts') {
                var headline = thread[0]; // drafts are not supposed to be threaded, so message is always 1st
                _FetchDraftMessage($div, headline, function() { // get content and remove div from list
                    onClickCB([ headline ]); // create a new thread, because the original is destroyed
                });
            } else {
                $targetDiv.find('.Headlines_entryCurrent').removeClass('Headlines_entryCurrent');
                $div.addClass('Headlines_entryCurrent');
                onClickCB(thread);
            }
        }
        return false;
    });

    $targetDiv.on('click', '.Headlines_check > div', function(ev) { // click on checkbox
        ev.stopImmediatePropagation();
        var $chk = $(this);
        var $divClicked = $chk.closest('div.Headlines_entry');
        var willCheck = $chk.hasClass('icoCheck0');
        $chk.toggleClass('icoCheck0', !willCheck).toggleClass('icoCheck1', willCheck);
        lastCheckWith = 'leftClick';

        if (ev.shiftKey && $prevClick !== null) { // will check a range
            var isIn = false;
            $targetDiv.find('div.Headlines_entry').each(function(idx, div) {
                var $div = $(div);
                if (isIn) { // we're in the middle of the selection range
                    $div.addClass('Headlines_entryChecked').find('.icoCheck0')
                        .removeClass('icoCheck0').addClass('icoCheck1');
                }
                if ($div.is($divClicked) || $div.is($prevClick)) { // boundary DIV?
                    if (!isIn) { // we've just entered the selection range
                        isIn = true;
                        $div.addClass('Headlines_entryChecked').find('.icoCheck0')
                            .removeClass('icoCheck0').addClass('icoCheck1');
                    } else { // last one of the selection range
                        return false; // break
                    }
                }
            });
        } else { // won't check a range
            willCheck ?
                $chk.closest('.Headlines_entry').addClass('Headlines_entryChecked') :
                $chk.closest('.Headlines_entry').removeClass('Headlines_entryChecked');
        }

        $prevClick = $divClicked; // keep
        onCheckCB(); // invoke user callback
    });
};
});
