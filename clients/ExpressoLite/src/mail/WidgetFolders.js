/*!
 * Expresso Lite
 * Widget to render the folder list.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App'
],
function($, App) {
App.LoadCss('mail/WidgetFolders.css');
return function(options) {
    var userOpts = $.extend({
        $elem: null, // jQuery object for the target DIV
        folderCache: []
    }, options);

    var THIS         = this;
    var $targetDiv   = userOpts.$elem; // shorthand
    var menu         = null; // context menu object
    var isFirstClick = true; // flag to avoid immediate refresh (unnecessary) on 1st click
    var curFolder    = null; // cache currently selected folder

    var onClickCB         = $.noop; // user callbacks
    var onTreeChangedCB   = $.noop;
    var onFolderUpdatedCB = $.noop;

    function _FindFolderLi(folder) {
        var $retLi = null; // given a folder object, find the LI to which it belongs
        $targetDiv.find('li').each(function(idx, li) {
            var $li = $(li);
            if ($li.data('folder').globalName === folder.globalName) {
                $retLi = $li;
                return false; // break
            }
        });
        return $retLi;
    }

    function _BuildDiv(folder, isExpanded) {
        var lnkToggle = '';
        if (folder.hasSubfolders) {
            lnkToggle = isExpanded ?
            '<a href="#" class="Folders_toggle" title="Recolher pasta"><div class="Folders_arrowDown"></div></a>' :
                '<a href="#" class="Folders_toggle" title="Expandir pasta"><div class="Folders_arrowRite"></div></a>';
        }
        var count = folder.unreadMails ? (folder.unreadMails+'/'+folder.totalMails) : folder.totalMails;
        var lnkText = '<div class="Folders_folderName">'+folder.localName+'</div> ' +
            '<span class="Folders_counter">('+count+')</span>';
        var text = folder.unreadMails ? '<b>'+lnkText+'</b>' : lnkText;
        var $div = $('<div class="Folders_text">'+lnkToggle+' '+text+'</div>');
        return $div;
    }

    function _BuildUl(folders, isRootLevel) {
        var $ul = $('<ul class="Folders_ul" style="padding-left:'+(isRootLevel?'0':'11')+'px;"></ul>');
        for (var i = 0; i < folders.length; ++i) {
            var $li = $('<li class="Folders_li"></li>');
            $li.data('folder', folders[i]); // keep folder object within LI
            $li.append(_BuildDiv(folders[i], false));
            if (folders[i].subfolders.length)
                _BuildUl(folders[i].subfolders, false).appendTo($li);
            $li.appendTo($ul);
        }
        return $ul;
    }

    function _UpdateOneFolder($li) {
        var defer = $.Deferred();
        if (!$li.hasClass('Folders_li')) {
            $li = $li.closest('.Folders_li');
        }
        var folder = $li.data('folder');
        var $counter = $li.find('.Folders_counter:first').replaceWith($('#icons .throbber').clone());

        App.Post('updateMessageCache', { folderId:folder.id })
        .always(function() { $li.find('.throbber:first').replaceWith($counter); })
        .fail(function(resp) {
            window.alert('Erro na consulta dos emails de "'+folder.localName+'".\n' +
                'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            defer.reject();
        }).done(function(stats) {
            var hasChanged = (folder.totalMails !== stats.totalMails) ||
                (folder.unreadMails !== stats.unreadMails);
            if (hasChanged) { // folder status changed
                folder.totalMails = stats.totalMails;
                folder.unreadMails = stats.unreadMails;

                if (folder.id === curFolder.id) { // current folder
                    THIS.redraw(folder);
                    onFolderUpdatedCB(folder);
                } else { // not current folder
                    folder.messages.length = 0; // force cache rebuild
                    folder.threads.length = 0;
                    THIS.redraw(folder);
                }
            }
            defer.resolve();
        });
        return defer.promise();
    }

    function _UpdateSubfolders($li) {
        var defer = $.Deferred();
        if (!$li.hasClass('Folders_li')) {
            $li = $li.closest('.Folders_li');
        }
        _UpdateOneFolder($li).done(function() {
            var all = $li.find('.Folders_li:visible').toArray(); // all updateable
            (function GoNext() {
                all.length ?
                    _UpdateOneFolder( $(all.shift()) ).done(GoNext) :
                    defer.resolve();
            })();
        });
        return defer.promise();
    }

    function _LoadSubfolders(parentFolder) {
        var defer = $.Deferred();
        var $divLoading = $(document.createElement('div'))
            .addClass('Folders_loading')
            .append('Carregando pastas... ')
            .append($(document.createElement('div'))
                .addClass('Folders_throbber')
            );

        if (parentFolder === null) { // root folder
            $divLoading.appendTo($targetDiv);

            App.Post('searchFolders')
            .always(function() { $divLoading.remove(); })
            .fail(function(resp) {
                window.alert('Erro na primeira consulta das pastas.\n' +
                    'Atualize a página para tentar novamente.\n' + resp.responseText);
                defer.reject();
            }).done(function(folders) {
                userOpts.folderCache.length = 0;
                userOpts.folderCache.push.apply(userOpts.folderCache, folders); // cache
                _BuildUl(folders, true).appendTo($targetDiv);
                defer.resolve();
            });
        } else { // non-root folder
            var $li = _FindFolderLi(parentFolder);
            $divLoading.appendTo($li);

            App.Post('searchFolders', { parentFolder:parentFolder.globalName })
            .always(function() { $divLoading.remove(); })
            .fail(function(resp) {
                window.alert('Erro na consulta das subpastas de '+parentFolder.localName+'\n' +
                    resp.responseText);
                defer.reject();
            }).done(function(subfolders) {
                parentFolder.subfolders.length = 0;
                parentFolder.subfolders.push.apply(parentFolder.subfolders, subfolders); // cache
                _BuildUl(subfolders, false).appendTo($li);
                defer.resolve();
                onTreeChangedCB();
            });
        }
        return defer.promise();
    }

    THIS.load = function() {
        var defer = $.Deferred();
        defer.resolve();
        return defer.promise();
    };

    THIS.loadRoot = function() {
        return _LoadSubfolders(null);
    };

    THIS.setCurrent = function(folder) {
        if (folder.id === null) { // usually when a search is made
            curFolder = folder;
            $targetDiv.find('.Folders_current').removeClass('Folders_current');
        } else {
            _FindFolderLi(folder).children('.Folders_text').trigger('click');
        }
        return THIS;
    };

    THIS.getCurrent = function() {
        return curFolder;
    };

    THIS.redraw = function(folder) {
        var $li = _FindFolderLi(folder);
        if ($li !== null) {
            var $div = $li.children('div:first');
            var $childUl = $div.next('ul');
            var isExpanded = $childUl.length && $childUl.is(':visible');
            var $newDiv = _BuildDiv(folder, isExpanded);
            if ($div.hasClass('Folders_current')) {
                $newDiv.addClass('Folders_current');
            }
            $div.replaceWith($newDiv);
        }
        return THIS;
    };

    THIS.expand = function(folder) {
        var $li = _FindFolderLi(folder);
        var $arrow = $li.find('.Folders_toggle:first > div[class^=Folders_arrow]');
        if (folder.hasSubfolders && !folder.subfolders.length) { // subfolders not cached yet
            $arrow.removeClass('Folders_arrowRite').addClass('Folders_arrowDown');
            return _LoadSubfolders(folder);
        } else {
            var $childUl = $li.children('ul:first');
            $childUl.toggle();
            var isVisible = $childUl.is(':visible');
            $arrow.toggleClass('Folders_arrowRite', !isVisible)
                .toggleClass('Folders_arrowDown', isVisible);
            return $.Deferred().resolve().promise();
        }
    };

    THIS.updateAll = function(onDone) {
        return _UpdateSubfolders($targetDiv.find('.Folders_li:first'));
    };

    THIS.onClick = function(callback) {
        onClickCB = callback; // onClick(folder)
        return THIS;
    };

    THIS.onTreeChanged = function(callback) {
        onTreeChangedCB = callback; // onTreeChanged()
        return THIS;
    };

    THIS.onFolderUpdated = function(callback) {
        onFolderUpdatedCB = callback; // onFolderUpdated(folder)
        return THIS;
    };

    $targetDiv.on('click', 'a.Folders_toggle', function() { // expand/collapse a folder
        $(this).blur();
        THIS.expand($(this).closest('li').data('folder'));
        return false;
    });

    $targetDiv.on('click', 'div.Folders_text', function() { // click on folder name
        var $li = $(this).closest('li');
        curFolder = $li.data('folder'); // cache
        $targetDiv.find('.Folders_current').removeClass('Folders_current');
        $(this).addClass('Folders_current');

        if (!curFolder.messages.length) { // if messages not cached yet
            if (isFirstClick) {
                isFirstClick = false;
                onClickCB(curFolder); // invoke user callback
            } else {
                var $counter = $li.find('.Folders_counter:first')
                    .replaceWith($('#icons .throbber').clone());

                App.Post('updateMessageCache', { folderId:curFolder.id })
                .always(function() { $li.find('.throbber:first').replaceWith($counter); })
                .fail(function(resp) {
                    window.alert('Erro ao atualizar a pasta "'+curFolder.localName+'".\n' +
                        'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                }).done(function(stats) {
                    var hasChanged = (curFolder.totalMails !== stats.totalMails) ||
                        (curFolder.unreadMails !== stats.unreadMails);
                    if (hasChanged) {
                        curFolder.totalMails = stats.totalMails;
                        curFolder.unreadMails = stats.unreadMails;
                        curFolder.messages.length = 0; // clear cache, will force reload
                        curFolder.threads.length = 0;
                        THIS.redraw(curFolder);
                    }
                    onClickCB(curFolder); // invoke user callback
                });
            }
        } else { // messages already cached, won't look for more right now
            onClickCB(curFolder); // invoke user callback
        }
        return false;
    });
};
});
