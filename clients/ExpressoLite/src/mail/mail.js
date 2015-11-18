/*!
 * Expresso Lite
 * Main script of email module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

require.config({
    baseUrl: '..',
    paths: { jquery: 'common-js/jquery.min' }
});

require(['jquery',
    'common-js/App',
    'common-js/UrlStack',
    'common-js/Layout',
    'common-js/ContextMenu',
    'common-js/Contacts',
    'mail/ThreadMail',
    'mail/WidgetCompose',
    'mail/WidgetFolders',
    'mail/WidgetHeadlines',
    'mail/WidgetMessages'
],
function($, App, UrlStack, Layout, ContextMenu, Contacts, ThreadMail,
    WidgetCompose, WidgetFolders, WidgetHeadlines, WidgetMessages) {
window.Cache = {
    MAILBATCH: App.GetUserInfo('mailBatch'),
    folders: [], // all folder objects
    layout: null, // renders the main page layout
    treeFolders: null, // folder rendering widget
    listHeadlines: null, // headlines list rendering widget
    subjectMenu: null, // dropdown menu at right of subject text
    listMessages: null, // messages list rendering widget
    wndCompose: null // compose modeless popup
};

App.Ready(function() {
    // Initialize page objects.
    Cache.layout = new Layout({
        userMail: App.GetUserInfo('mailAddress'),
        $menu: $('#leftColumn'),
        $middle: $('#middleBody'),
        $right: $('#rightBody')
    });
    Cache.wndCompose = new WidgetCompose({
        address: App.GetUserInfo('mailAddress'),
        signature: App.GetUserInfo('mailSignature'),
        folderCache: Cache.folders
    });
    Cache.treeFolders = new WidgetFolders({ $elem:$('#foldersArea'), folderCache:Cache.folders });
    Cache.listHeadlines = new WidgetHeadlines({ $elem:$('#headlinesArea'), folderCache:Cache.folders });
    Cache.listMessages = new WidgetMessages({ $elem:$('#messagesArea'), folderCache:Cache.folders, wndCompose:Cache.wndCompose });
    Cache.subjectMenu = new ContextMenu({ $btn:$('#subjectMenu') }); // actions for the whole opened thread

    // Some initial work.
    UrlStack.keepClean();
    $('#headlinesFooter').css('display', 'none'); // hidden until first headlines load
    Contacts.loadPersonal();

    // Load templates of widgets.
    $.when(
        Cache.layout.load(),
        Cache.treeFolders.load(),
        Cache.listHeadlines.load(),
        Cache.listMessages.load(),
        Cache.wndCompose.load()
    ).done(function() {
        $('#btnUpdateFolders,#btnCompose').css('display', 'none');
        Cache.layout.setLeftMenuVisibleOnPhone(true).done(function() {
            Cache.treeFolders.loadRoot().done(function() {
                Cache.treeFolders.expand(Cache.folders[0]).done(function() { // expand 1st folder (probably inbox)...
                    Cache.treeFolders.setCurrent(Cache.folders[0]); // ...and select it
                    $('#btnUpdateFolders,#btnCompose').css('display', '');
                });
            });
        });

        // Setup events.
        Cache.layout
            .onKeepAlive(function() { $('#btnUpdateFolders').trigger('click'); })
            .onHideRightPanel(function() { SetMessagesPanelVisible(false); })
            .onSearch(Search); // when user performs a search
        Cache.treeFolders
            .onClick(LoadFirstHeadlines)
            .onTreeChanged(RebuildHeadlinesContextMenu)
            .onFolderUpdated(LoadNewHeadlines);
        Cache.listHeadlines
            .onClick(HeadlineClicked)
            .onCheck(RebuildHeadlinesContextMenu)
            .onMarkRead(HeadlineMarkedRead)
            .onMove(ThreadMoved);
        Cache.listMessages
            .onView(function(folder, headline) { Cache.listHeadlines.redraw(headline); Cache.treeFolders.redraw(folder); })
            .onMarkRead(MessageMarkedRead)
            .onMove(MailMoved);
        Cache.wndCompose
            .onClose(function() { })
            .onSend(MailSent)
            .onDraft(DraftSaved);

        $('#loadMore').on('click', LoadMoreHeadlines);
        $('#btnUpdateFolders').on('click', CheckNewMessagesInFolders);
        $('#btnCompose').on('click', ShowComposePopup);
    });
});

function UpdatePageTitle() {
    var folder = Cache.treeFolders.getCurrent();
    var counter = (folder.unreadMails > 0) ? '('+folder.unreadMails+') ' : '';
    document.title = counter+folder.localName+' - '+App.GetUserInfo('mailAddress')+' - ExpressoBr';
    Cache.layout.setTitle(Cache.layout.isRightPanelVisible() ?
        'voltar' :
        folder.localName+(folder.unreadMails ? ' ('+folder.unreadMails+')' : '')
    );
}

function UpdateHeadlineFooter() {
    var folder = Cache.treeFolders.getCurrent();
    if (!folder.messages.length) {
        $('#loadedCount').text(folder.id === null ?
            'Nenhum email encontrado.' :
            'A pasta '+folder.localName+' está vazia.');
    } else {
        var num = (folder.messages.length < folder.totalMails) ?
            folder.messages.length+' de '+folder.totalMails :
            folder.messages.length;
        var s = folder.messages.length > 1 ? 's' : '';
        $('#loadedCount').text(num+' email'+s+' carregado'+s);
    }
    (folder.messages.length >= folder.totalMails) ? // no more emails to be fetched from server
        $('#loadMore').hide() :
        $('#loadMore').val('carregar +' +
            Math.min(folder.totalMails - folder.messages.length, Cache.MAILBATCH)).show();
}

function RebuildHeadlinesContextMenu() {
    Cache.layout.getContextMenu().purge(); // rebuild the headlines context menu
    if (Cache.listHeadlines.getChecked().length === 0) {
        Cache.layout.setContextMenuVisible(false);
    } else {
        Cache.layout.getContextMenu()
            .addOption('Desselecionar todas', Cache.listHeadlines.clearChecked)
            .addOption('Marcar como lida', function() { Cache.listHeadlines.markRead(true); })
            .addOption('Marcar como não lida', function() { Cache.listHeadlines.markRead(false); })
            .addOption('Alterar destaque', Cache.listHeadlines.toggleStarred)
            .addOption('Apagar', Cache.listHeadlines.deleteMessages)
            .addHeader('Mover para...');

        var curFolder = Cache.treeFolders.getCurrent();
        var MenuRenderFolderLevel = function(folders, level) {
            $.each(folders, function(idx, folder) {
                if (folder.globalName !== curFolder.globalName) { // avoid move to current folder
                    Cache.layout.getContextMenu().addOption(folder.localName, function() {
                        Cache.listHeadlines.moveMessages(folder); // pass folder object
                    }, level); // indentation
                }
                MenuRenderFolderLevel(folder.subfolders, level + 1);
            });
        };
        MenuRenderFolderLevel(Cache.folders, 0);
        Cache.layout.setContextMenuVisible(!Cache.layout.isRightPanelVisible());
    }
}

function RebuildSubjectThreadMenu(thread) {
    $('#subjectMenu').toggle(thread.length > 1);
    if (thread.length > 1) {
        Cache.subjectMenu.purge()
            .addOption('Marcar conversa como lida', function() {
                Cache.listHeadlines.setCurrentChecked();
                SetMessagesPanelVisible(false);
                Cache.listHeadlines.markRead(true);
            }).addOption('Marcar conversa como não lida', function() {
                Cache.listHeadlines.setCurrentChecked();
                SetMessagesPanelVisible(false);
                Cache.listHeadlines.markRead(false);
            }).addOption('Apagar conversa', function() {
                Cache.listHeadlines.setCurrentChecked();
                SetMessagesPanelVisible(false);
                Cache.listHeadlines.deleteMessages();
            }).addHeader('Mover conversa para...');

        var curFolder = Cache.treeFolders.getCurrent();
        var MenuRenderFolderLevel = function(folders, level) {
            $.each(folders, function(idx, folder) {
                if (folder.globalName !== curFolder.globalName) { // avoid move to current folder
                    Cache.subjectMenu.addOption(folder.localName, function() {
                        Cache.listHeadlines.setCurrentChecked();
                        SetMessagesPanelVisible(false);
                        Cache.listHeadlines.moveMessages(folder); // pass folder object
                    }, level); // indentation
                }
                MenuRenderFolderLevel(folder.subfolders, level + 1);
            });
        };
        MenuRenderFolderLevel(Cache.folders, 0);
    }
}

function SetMessagesPanelVisible(isShow) {
    if (isShow) {
        Cache.layout.setTitle('voltar');
        Cache.layout.setContextMenuVisible(false);
        Cache.layout.setRightPanelVisible(true);
    } else {
        UpdatePageTitle();
        Cache.layout.setRightPanelVisible(false);
        RebuildHeadlinesContextMenu();
        window.setTimeout(function() { Cache.listHeadlines.clearCurrent(); }, 400); // flash hint before go

        var openedThread = Cache.listHeadlines.getCurrent();
        if (openedThread !== null) {
            var cyPos = Cache.listHeadlines.calcScrollTopOf(openedThread), // scroll headlines to have selected at center
                cyHalf = $('#middleBody').outerHeight() / 2;
            $('#middleBody').scrollTop(cyPos > cyHalf ? cyPos - cyHalf : 0);
        }
    }

    Cache.listHeadlines.setCheckboxesVisible(!isShow);
}

function Search(text) {
    SetMessagesPanelVisible(false);
    $('#middleBody').scrollTop(0);
    $('#headlinesFooter').css('display', 'none');

    text = $.trim(text.replace(/\//g, '').replace(/\\/g, '')); // remove slashes, they break Tine
    if (text === '') {
        var curFolder = Cache.treeFolders.getCurrent();
        if (curFolder.searchedFolder !== undefined) { // we currently have a search displayed?
            curFolder = curFolder.searchedFolder;
        }
        Cache.treeFolders.setCurrent(curFolder); // nothing to search, simply show current folder
    } else {
        var curFolder = Cache.treeFolders.getCurrent();
        var fname = (curFolder.searchedFolder === undefined) ?
            curFolder.localName : curFolder.searchedFolder.localName;
        Cache.layout.setTitle('Buscando em '+fname+'...');

        Cache.listHeadlines.searchMessages(text, Cache.MAILBATCH).fail(function() {
            Cache.treeFolders.setCurrent(Cache.treeFolders.getCurrent());
        }).always(function(virtFolder) {
            $('#headlinesFooter').css('display', '');
            Cache.treeFolders.setCurrent(virtFolder); // virtual folder with search result
            UpdatePageTitle();
            UpdateHeadlineFooter();
        });
    }
}

function LoadFirstHeadlines(folder) {
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        $('#middleBody').scrollTop(0);
        $('#headlinesFooter').css('display', 'none');
        UpdatePageTitle();
        Cache.listHeadlines.loadFolder(folder, Cache.MAILBATCH).done(function() {
            $('#headlinesFooter').css('display', '');
            UpdateHeadlineFooter();
        });
    });
}

function LoadMoreHeadlines() {
    $('#headlinesFooter').css('display', 'none');
    SetMessagesPanelVisible(false);
    Cache.listHeadlines.loadMore(Cache.MAILBATCH).done(function() {
        $('#headlinesFooter').css('display', '');
        UpdatePageTitle();
        UpdateHeadlineFooter();
    });
}

function LoadNewHeadlines(folder) {
    Cache.listHeadlines.loadNew(Cache.MAILBATCH, function() {
        UpdatePageTitle();
        UpdateHeadlineFooter();
        $('#middleBody').scrollTop(0); // scroll to top
    });
}

function HeadlineClicked(thread) {
    var curFolder = Cache.treeFolders.getCurrent();
    if (curFolder.globalName === 'INBOX/Drafts') {
        Cache.wndCompose.show({ draft:thread[0] }); // drafts are not supposed to be threaded, so message is always 1st
    } else {
        SetMessagesPanelVisible(true);
        var curFolder = Cache.treeFolders.getCurrent();
        Cache.listMessages.render(thread, curFolder); // headlines sorted newest first
        $('#subjectText').text(thread[thread.length-1].subject);
        RebuildSubjectThreadMenu(thread);
    }

    var cyPos = Cache.listHeadlines.calcScrollTopOf(thread), // scroll headlines to have selected at center
        cyHalf = $('#middleBody').outerHeight() / 2;
    $('#middleBody').scrollTop(cyPos > cyHalf ? cyPos - cyHalf : 0);
}

function ThreadMoved(destFolder) {
    SetMessagesPanelVisible(false);
    if (destFolder !== null) {
        Cache.treeFolders.redraw(destFolder);
    }
    var curFolder = Cache.treeFolders.getCurrent();
    Cache.treeFolders.redraw(curFolder);
    UpdateHeadlineFooter();
    UpdatePageTitle();
    RebuildHeadlinesContextMenu();
    if (curFolder.id === null) { // we're on a search result
        $('#btnUpdateFolders').trigger('click');
    }
}

function HeadlineMarkedRead(folder) {
    SetMessagesPanelVisible(false);
    Cache.treeFolders.redraw(folder);
    UpdatePageTitle();
    if (folder.id === null) { // we're on a search result
        $('#btnUpdateFolders').trigger('click');
    }
}

function MessageMarkedRead(folder, headline) {
    Cache.listHeadlines.redraw(headline);
    Cache.treeFolders.redraw(folder);
    UpdatePageTitle();
    if (folder.id === null) { // we're on a search result
        $('#btnUpdateFolders').trigger('click');
    }

    var curThread = Cache.listHeadlines.getCurrent();
    if (curThread.length === 1 && headline.unread) {
        SetMessagesPanelVisible(false);
    }
}

function MailMoved(destFolder, origThread) {
    Cache.listHeadlines.redrawByThread(origThread, function() {
        if (!Cache.listMessages.count()) {
            SetMessagesPanelVisible(false);
        }
        if (destFolder !== null) {
            Cache.treeFolders.redraw(destFolder);
        }
        Cache.treeFolders.redraw(Cache.treeFolders.getCurrent());
        UpdateHeadlineFooter();
    });
}

function CheckNewMessagesInFolders() {
    $('#btnUpdateFolders,#btnCompose').css('display', 'none');
    $('#txtUpdateFolders').css('display', 'inline-block');
    Cache.treeFolders.updateAll().done(function() {
        $('#btnUpdateFolders,#txtUpdateFolders,#btnCompose').css('display', '');
    });
}

function ShowComposePopup() {
    $('#btnCompose').blur();
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        Cache.wndCompose.show({ curFolder:Cache.treeFolders.getCurrent() });
    });
}

function MailSent(reMsg, fwdMsg, draftMsg, message) {
    var sentFolder = ThreadMail.FindFolderByGlobalName('INBOX/Sent', Cache.folders);
    ++sentFolder.totalMails;
    Cache.treeFolders.redraw(sentFolder);

    if (reMsg !== null) {
        Cache.listHeadlines.redraw(reMsg); // if currently shown, will redraw
        Cache.listMessages.redrawIcons(reMsg);
    } else if (fwdMsg !== null) {
        Cache.listHeadlines.redraw(fwdMsg);
        Cache.listMessages.redrawIcons(fwdMsg);
    }

    if (Cache.treeFolders.getCurrent().globalName === 'INBOX/Sent') {
        Cache.listHeadlines.loadNew(1);
    } else {
        sentFolder.messages.length = 0; // force cache rebuild
        sentFolder.threads.length = 0;
    }

    if (draftMsg !== null) { // a draft was sent
        var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', Cache.folders);
        Cache.treeFolders.redraw(draftFolder);
        draftFolder.messages.length = 0; // force cache rebuild
        draftFolder.threads.length = 0;
        if (Cache.treeFolders.getCurrent().globalName === 'INBOX/Drafts') {
            LoadFirstHeadlines(draftFolder);
        }
    }

    var contacts = [];
    if (message.to) {
        $.merge(contacts, message.to.split(','));
    }
    if (message.bcc) {
        $.merge(contacts, message.bcc.split(','));
    }
    if (message.cc) {
        $.merge(contacts, message.cc.split(','));
    }
    Contacts.updatePersonalCache(contacts);

    UpdateHeadlineFooter();
}

function DraftSaved() {
    var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', Cache.folders);
    ++draftFolder.totalMails;
    Cache.treeFolders.redraw(draftFolder);
    draftFolder.messages.length = 0; // force cache rebuild
    draftFolder.threads.length = 0;
    if (Cache.treeFolders.getCurrent().globalName === 'INBOX/Drafts') {
        LoadFirstHeadlines(draftFolder);
    }
}
});
