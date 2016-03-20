/*!
 * Expresso Lite
 * Widget to render the compose email fields.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'common-js/DateFormat',
    'common-js/Dialog',
    'common-js/TextBadges',
    'common-js/ContactsAutocomplete',
    'mail/ThreadMail',
    'mail/WidgetAttacher'
],
function($, App, DateFormat, Dialog, TextBadges, ContactsAutocomplete, ThreadMail, WidgetAttacher) {
App.LoadCss('mail/WidgetCompose.css');
return function(options) {
    var userOpts = $.extend({
        address: 'user@domain', // user email address
        signature: '', // user footer email signature
        folderCache: [] // array with all email folders
    }, options);

    var THIS      = this;
    var onCloseCB = $.noop; // user callbacks
    var onSendCB  = $.noop;
    var onDraftCB = $.noop;
    var $tpl      = null; // jQuery object with our HTML template
    var popup     = null; // Dialog object, created on show()
    var txtBadgesTo = null, txtBadgesCc = null, txtBadgesBcc = null;
    var autocompTo = null, autocompCc = null, autocompBcc = null;
    var attacher  = null; // WidgetAttacher object, created on show()
    var msg       = { fwd:null, re:null, reAll:null, draft:null }; // we have a forwarded/replied/draft message
    var isSending = false; // a "send" async request is running

    function _DeleteOldDraftIfAny(draftMsgObj, onDone) {
        if (draftMsgObj !== null) { // are we editing an old draft?
            popup.setCaption( $(document.createElement('span'))
                .append('Atualizando rascunho... ')
                .append($('#Compose_template .Compose_throbber').clone()) );

            App.Post('deleteMessages', { messages:draftMsgObj.id, forever:1 })
            .fail(function(resp) {
                window.alert('Erro ao apagar o rascunho antigo.\n' +
                    'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
            }).done(function(status) {
                var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', userOpts.folderCache);
                --draftFolder.totalMails;
                if (onDone !== undefined) onDone();
            });
        } else {
            if (onDone !== undefined) onDone(); // do nothing and just invoke callback
        }
    }

    function _ResizeWriteField() {
        if (App.IsPhone()) return; // phones will scroll dialog vertically
        var cy = popup.getContentArea().cy;
        var cyUsed = 0;
        $tpl.children(':visible:not(.Compose_body)').each(function(idx, elem) {
            cyUsed += $(elem).outerHeight(true);
        });
        $tpl.find('.Compose_body').css('height', (cy - cyUsed - 30)+'px');
    }

    function _PrepareBodyToQuote(action, headline) {
        var out = '';
        if (action === 'draft') {
            return headline.body.message;
        } else if (action === 'reply' || action === 'replyToAll') { // prepare mail content to be replied
            out = '<br/>Em '+DateFormat.Medium(headline.received)+', ' +
                headline.from.name+' escreveu:' +
                '<blockquote>'+headline.body.message+'<br/>' +
                (headline.body.quoted !== null ? headline.body.quoted : '') +
                '</blockquote>';
        } else if (action === 'forward') { // prepare mail content to be forwarded
            out = '<br/>-----Mensagem original-----<br/>' +
                '<b>Assunto:</b> '+headline.subject+'<br/>' +
                '<b>Remetente:</b> "'+headline.from.name+'" &lt;'+headline.from.email+'&gt;<br/>' +
                '<b>Para:</b> '+headline.to.join(', ')+'<br/>' +
                (headline.cc.length ? '<b>Cc:</b> '+headline.cc.join(', ')+'<br/>' : '') +
                '<b>Data:</b> '+DateFormat.Medium(headline.received)+'<br/><br/>' +
                headline.body.message+'<br/>' +
                (headline.body.quoted !== null ? headline.body.quoted : '');
        }
        return '<br/><br/>'+userOpts.signature+'<br/>'+out; // append user signature
    }

    function _UserWroteSomethingNew() {
        function removeSpacesAndTrimCommas(txt) {
            return txt.replace(/\s/g, '')
                .replace(/^[,\s]+|[,\s]+$/g, '');
        }
        var curAddr = {
            to: removeSpacesAndTrimCommas(txtBadgesTo.getBadgeValues().join(',')),
            cc: removeSpacesAndTrimCommas(txtBadgesCc.getBadgeValues().join(',')),
            bcc: removeSpacesAndTrimCommas(txtBadgesBcc.getBadgeValues().join(','))
        };
        var changedDraftAddr = (msg.draft !== null && (
            curAddr.to !== removeSpacesAndTrimCommas(msg.draft.to.join(',')) ||
            curAddr.cc !== removeSpacesAndTrimCommas(msg.draft.cc.join(',')) ||
            curAddr.bcc !== removeSpacesAndTrimCommas(msg.draft.bcc.join(',')) ));
        if (changedDraftAddr) {
            return true;
        }

        var origAction = 'new';
        var origMsg = null;

        if (msg.fwd !== null) {
            origAction = 'forward';
            origMsg = msg.fwd;
        } else if (msg.re !== null) {
            origAction = 'reply';
            origMsg = msg.re;
        } else if (msg.reAll !== null) {
            origAction = 'replyToAll';
            origMsg = msg.reAll;
        } else if (msg.draft !== null) {
            origAction = 'draft';
            origMsg = msg.draft;
        } else { // new message being written from scratch
            if (attacher.getAll().length) {
                return true; // an attachment has been added
            }
            var subj = $.trim($tpl.find('.Compose_subject').val());
            if (subj !== '' || curAddr.to !== '' || curAddr.cc !== '' || curAddr.bcc !== '') {
                return true;
            }
        }

        // Compare current message body with the one the user had at the
        // moment he opened the popup, to see if he changed the body.
        var origHtmlBody = _PrepareBodyToQuote(origAction, origMsg);
        return $tpl.find('.Compose_body').text() !== $(origHtmlBody).text();
    }

    function _ValidateAddresses(strAddrs) {
        var mails = strAddrs.split(/[\s,;]+/); // single string with all addresses into array
        for (var i = 0; i < mails.length; ++i) {
            if (!/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(mails[i])) {
                return { status:false, address:mails[i] }; // invalid address is returned
            }
        }
        return { status:true };
    }

    function _JoinReplyAddresses(headline) {
        var ourMail = userOpts.address;
        var clonedTo = $.grep(headline.to, function(elem, i) {
                return (elem !== ourMail) &&
                    (elem !== headline.from.email) &&
                    _ValidateAddresses(elem).status;
            }),
            clonedCc = $.grep(headline.cc, function(elem, i) {
                return (elem !== ourMail) &&
                    (elem !== headline.from.email) &&
                    _ValidateAddresses(elem).status;
            });
        if (clonedTo.length && clonedCc.length) {
            clonedTo.push.apply(clonedTo, clonedCc);
            return clonedTo;
        } else if (clonedTo.length) {
            return clonedTo;
        } else if (clonedCc.length) {
            return clonedCc;
        }
        return [];
    }

    function _NameFromAddr(addr) {
        var name = addr.substr(0, addr.indexOf('@')).toLowerCase();
        var parts = name.split(/[\.-]+/);
        for (var i = 0; i < parts.length; ++i) {
            parts[i] = parts[i][0].toUpperCase() + parts[i].slice(1);
        }
        return parts.join(' ');
    }

    function _BuildMessageObject() {
        var message = {
            subject: $.trim($tpl.find('.Compose_subject').val()),
            body: $tpl.find('.Compose_body').html(),
            to: txtBadgesTo.getBadgeValues().join(',').replace(/^[,\s]+|[,\s]+$/g, ''), // trim spaces and commas
            cc: txtBadgesCc.getBadgeValues().join(',').replace(/^[,\s]+|[,\s]+$/g, ''),
            bcc: txtBadgesBcc.getBadgeValues().join(',').replace(/^[,\s]+|[,\s]+$/g, ''),
            isImportant: '0', // 0|1 means false|true
            replyToId: null,
            forwardFromId: null,
            origDraftId: null,
            attachs: ''
        };

        if ($tpl.find('.Compose_important').is(':checked')) {
            message.isImportant = '1';
        }

        if (msg.re !== null) { // is this message a reply to other one?
            message.replyToId = msg.re.id;
        } else if (msg.reAll !== null) {
            message.replyToId = msg.reAll.id;
        } else if (msg.fwd !== null) { // is this message a forwarding of other one?
            message.forwardFromId = msg.fwd.id;
        }

        if (msg.draft !== null) { // are we editing an existing draft?
            message.origDraftId = msg.draft.id;
        }

        var attachments = attacher.getAll();
        if (attachments.length) {
            message.attachs = JSON.stringify(attachments); // attachments already uploaded to temp area
        }

        return message;
    }

    function _ValidateSend(message, allowBlankDest) {
        if (message.subject == '') {
            window.alert('O email está sem assunto.');
            $tpl.find('.Compose_subject').focus();
            return false;
        } else if (allowBlankDest === undefined && message.to == '' && message.cc == '' && message.bcc == '') {
            window.alert('Não há destinatários para o email.');
            $tpl.find('.Compose_to').focus();
            return false;
        }

        if (message.to != '') {
            var valid = _ValidateAddresses(message.to);
            if (!valid.status) {
                window.alert('O campo "para" possui um endereço inválido:\n'+valid.address);
                return false;
            }
        }
        if (message.cc != '') {
            var valid = _ValidateAddresses(message.cc);
            if (!valid.status) {
                window.alert('O campo "Cc" possui um endereço inválido:\n'+valid.address);
                return false;
            }
        }
        if (message.bcc != '') {
            var valid = _ValidateAddresses(message.bcc);
            if (!valid.status) {
                window.alert('O campo "Bcc" possui um endereço inválido:\n'+valid.address);
                return false;
            }
        }

        return true;
    }

    function _PopupClosed() {
        autocompTo.hide(); // cleanup
        autocompCc.hide();
        autocompBcc.hide();
        msg.fwd = null;
        msg.re = null;
        msg.reAll = null;
        msg.draft = null;
        popup = null;
        attacher.removeAll();
        attacher = null;
        $tpl = null; // discard the cloned HTML template
        onCloseCB(); // invoke user callback
    }

    function _FillNewFields(showOpts) {
        var isNewMessage = showOpts.forward === null &&
            showOpts.reply === null &&
            showOpts.replyToAll === null &&
            showOpts.draft === null;

        if (isNewMessage) {
            $tpl.find('.Compose_body').html(_PrepareBodyToQuote('new', null));
        } else if (showOpts.forward !== null) {
            msg.fwd = showOpts.forward; // keep forwarded headline
            $tpl.find('.Compose_subject').val('Fwd: '+msg.fwd.subject);
            attacher.rebuildFromMsg(msg.fwd); // when forwarding, keep attachments
        } else if (showOpts.reply !== null) {
            msg.re = showOpts.reply; // keep replied headline
            $tpl.find('.Compose_subject').val('Re: '+msg.re.subject);
            txtBadgesTo.addBadge(_NameFromAddr(msg.re.from.email), msg.re.from.email);
        } else if (showOpts.replyToAll !== null) {
            msg.reAll = showOpts.replyToAll; // keep replied headline
            $tpl.find('.Compose_subject').val('Re: '+msg.reAll.subject);
            txtBadgesTo.addBadge(_NameFromAddr(msg.reAll.from.email), msg.reAll.from.email);

            var replyAddrs = _JoinReplyAddresses(msg.reAll); // array
            if (replyAddrs.length) {
                $tpl.find('.Compose_ccToggle').hide();
                $tpl.find('.Compose_ccBlock').show();
                for (var i = 0; i < replyAddrs.length; ++i) {
                    txtBadgesCc.addBadge(_NameFromAddr(replyAddrs[i]),
                        replyAddrs[i].toLowerCase());
                }
            }
        } else if (showOpts.draft !== null) {
            msg.draft = showOpts.draft; // keep draft headline
            $tpl.find('.Compose_subject').val(msg.draft.subject);
            for (var i = 0; i < msg.draft.to.length; ++i) {
                txtBadgesTo.addBadge(_NameFromAddr(msg.draft.to[i]),
                    msg.draft.to[i].toLowerCase());
            }
            if (msg.draft.cc.length) {
                $tpl.find('.Compose_ccToggle').hide();
                $tpl.find('.Compose_ccBlock').show();
                for (var i = 0; i < msg.draft.cc.length; ++i) {
                    txtBadgesCc.addBadge(_NameFromAddr(msg.draft.cc[i]),
                        msg.draft.cc[i].toLowerCase());
                }
            }
            if (msg.draft.bcc.length) {
                $tpl.find('.Compose_bccToggle').hide();
                $tpl.find('.Compose_bccBlock').show();
                for (var i = 0; i < msg.draft.bcc.length; ++i) {
                    txtBadgesBcc.addBadge(_NameFromAddr(msg.draft.bcc[i]),
                        msg.draft.bcc[i].toLowerCase());
                }
            }
            attacher.rebuildFromMsg(msg.draft); // keep attachments
        }
        _ResizeWriteField();
    }

    function _SetEvents() {
        popup.onUserClose(function() { // when user clicked X button
            if (_UserWroteSomethingNew()) {
                var question = (msg.draft === null) ?
                    'Deseja descartar este email?' :
                    'Deseja descartar as modificações?';
                if (window.confirm(question)) {
                    popup.close();
                }
            } else {
                popup.close();
            }
        });

        popup.onClose(_PopupClosed); // when dialog is being dismissed

        popup.onResize(_ResizeWriteField);

        attacher.onContentChange(function() {
            $tpl.find('.Compose_attacher').toggle(attacher.getAll().length > 0);
            _ResizeWriteField();
        });

        function onClickShowField(cc, txtBadges) {
            $tpl.find('.Compose_'+cc+'Toggle').hide();
            $tpl.find('.Compose_'+cc+'Block').show();
            txtBadges.setFocus();
            _ResizeWriteField();
        }
        $tpl.find('.Compose_ccToggle').on('click', function() { onClickShowField('cc', txtBadgesCc); });
        $tpl.find('.Compose_bccToggle').on('click', function() { onClickShowField('bcc', txtBadgesBcc); });

        function onBlurBadges(text, cc, txtBadges) {
            if (!text.length && !txtBadges.getBadgeValues().length) {
                $tpl.find('.Compose_'+cc+'Block').hide(); // if field is empty, hide field & show button
                $tpl.find('.Compose_'+cc+'Toggle').show();
                _ResizeWriteField();
            }
        }
        txtBadgesCc.onBlur(function(text) { onBlurBadges(text, 'cc', txtBadgesCc); });
        txtBadgesBcc.onBlur(function(text) { onBlurBadges(text, 'bcc', txtBadgesBcc); });

        txtBadgesTo.onRemove(function() { _ResizeWriteField(); });
        txtBadgesCc.onRemove(function() { _ResizeWriteField(); });
        txtBadgesBcc.onRemove(function() { _ResizeWriteField(); });

        function onAutocompSelect(addrs, autocomp, txtBadges) {
            var promises = [];
            for (var i = 0; i < addrs.length; ++i) {
                promises.push( txtBadges.addBadge( // a new address was selected, make it a badge
                    _NameFromAddr(addrs[i]),
                    addrs[i].toLowerCase()) );
            }
            _ResizeWriteField();
            $.when.apply(null, promises).done(function() {
                $tpl.find('.Compose_fieldAddr').scrollTop(999999); // scroll all to bottom, just in case
                _ResizeWriteField();
            });
        }
        autocompTo.onSelect(function(addrs) { onAutocompSelect(addrs, autocompTo, txtBadgesTo); });
        autocompCc.onSelect(function(addrs) { onAutocompSelect(addrs, autocompCc, txtBadgesCc); });
        autocompBcc.onSelect(function(addrs) { onAutocompSelect(addrs, autocompBcc, txtBadgesBcc); });

        autocompTo.onBackspace(function() { txtBadgesTo.removeLastBadge(); });
        autocompCc.onBackspace(function() { txtBadgesCc.removeLastBadge(); });
        autocompBcc.onBackspace(function() { txtBadgesBcc.removeLastBadge(); });

        $tpl.find('.Compose_send').on('click', function() { // send email
            $(this).blur();

            var txtBadges = [ txtBadgesTo, txtBadgesCc, txtBadgesBcc ];
            for (var i = 0; i < 3; ++i) { // all addresses must be in badges
                if (txtBadges[i].getInputField().val().length) {
                    window.alert('Endereço de email inválido.');
                    txtBadges[i].getInputField().focus();
                    return;
                }
            }

            var message = _BuildMessageObject();
            if (_ValidateSend(message)) {
                isSending = true;
                popup.removeCloseButton();
                popup.setCaption( $(document.createElement('span'))
                    .append('Enviando email... ')
                    .append($('#Compose_template .Compose_throbber').clone()) );
                popup.toggleMinimize();

                var reMsg = (msg.re === null) ? msg.reAll : msg.re, // re and reAll are returned as the same object
                    fwdMsg = msg.fwd,
                    draftMsg = msg.draft; // cache to send to callback, since they'll soon be nulled by a close()

                App.Post('saveMessage', message)
                .fail(function(resp) {
                    window.alert('Erro ao enviar email.\n' +
                        'Sua interface está inconsistente, pressione F5.\n'+resp.responseText);
                    isSending = false;
                    popup.close();
                }).done(function(status) {
                    _DeleteOldDraftIfAny(draftMsg, function() {
                        if (reMsg !== null) reMsg.replied = true; // update cache
                        if (fwdMsg !== null) fwdMsg.forwarded = true;
                        isSending = false;
                        popup.close();
                        onSendCB(reMsg, fwdMsg, draftMsg, message); // invoke user callback
                    });
                });
            }
        });

        $tpl.find('.Compose_draft').on('click', function() { // save as draft
            $(this).blur();
            var message = _BuildMessageObject();
            if (_ValidateSend(message, 'allowBlankDest')) {
                isSending = true;
                popup.removeCloseButton();
                popup.setCaption( $(document.createElement('span'))
                    .append('Salvando rascunho... ')
                    .append($('#Compose_template .Compose_throbber').clone()) );
                popup.toggleMinimize();
                var draftFolder = ThreadMail.FindFolderByGlobalName('INBOX/Drafts', userOpts.folderCache);

                App.Post('saveMessageDraft', $.extend({ draftFolderId:draftFolder.id }, message))
                .fail(function(resp) {
                    window.alert('Erro ao salvar rascunho.\n' +
                        'Sua interface está inconsistente, pressione F5.\n' + resp.responseText);
                    isSending = false;
                    popup.close();
                }).done(function(status) {
                    _DeleteOldDraftIfAny(msg.draft, function() {
                        isSending = false;
                        popup.close();
                        onDraftCB(); // invoke user callback
                    });
                });
            }
        });

        $tpl.find('.Compose_attachNew').on('click', function() {
            attacher.newAttachment();
            var $subj = $tpl.find('.Compose_subject');
            $subj.focus();
            $subj[0].setSelectionRange($subj.val().length, $subj.val().length);
        });
    }

    function _CreateNewDialog(showOpts) {
        var defer = $.Deferred();
        $tpl = $('#Compose_template .Compose_panel').clone(); // create new HTML template object

        popup = new Dialog({ // create new modeless dialog object
            $elem: $tpl,
            caption: 'Escrever email',
            width: 680,
            height: $(window).outerHeight() - 120,
            minWidth: 300,
            minHeight: 450,
            modal: false
        });
        popup.show().done(function() {
            var isNewMessage = showOpts.forward === null &&
                showOpts.reply === null &&
                showOpts.replyToAll === null &&
                showOpts.draft === null;

            if (isNewMessage) {
                txtBadgesTo.setFocus();
            } else if (showOpts.forward !== null) {
                $tpl.find('.Compose_body').html(_PrepareBodyToQuote('forward', msg.fwd));
                txtBadgesTo.setFocus();
            } else if (showOpts.reply !== null) {
                $tpl.find('.Compose_body').html(_PrepareBodyToQuote('reply', msg.re)).focus();
            } else if (showOpts.replyToAll !== null) {
                $tpl.find('.Compose_body').html(_PrepareBodyToQuote('replyToAll', msg.reAll)).focus();
            } else if (showOpts.draft !== null) {
                $tpl.find('.Compose_body').html(_PrepareBodyToQuote('draft', msg.draft)).focus();
                $tpl.find('.Compose_important').prop('checked', msg.draft.important);
            }
            defer.resolve();
        });

        txtBadgesTo = new TextBadges({ $target:$tpl.find('.Compose_to'), inputType:'email' });
        txtBadgesCc = new TextBadges({ $target:$tpl.find('.Compose_cc'), inputType:'email' });
        txtBadgesBcc = new TextBadges({ $target:$tpl.find('.Compose_bcc'), inputType:'email' });

        autocompTo = new ContactsAutocomplete({
            $txtField: txtBadgesTo.getInputField(),
            $anchorElem: $tpl.find('.Compose_to'),
            $contentPanel: $tpl
        });
        autocompCc = new ContactsAutocomplete({
            $txtField: txtBadgesCc.getInputField(),
            $anchorElem: $tpl.find('.Compose_cc'),
            $contentPanel: $tpl
        });
        autocompBcc = new ContactsAutocomplete({
            $txtField: txtBadgesBcc.getInputField(),
            $anchorElem: $tpl.find('.Compose_bcc'),
            $contentPanel: $tpl
        });

        if (!App.IsPhone()) {
            $tpl.find('.Compose_subject').removeAttr('placeholder');
        }

        attacher = new WidgetAttacher({ $elem:$tpl.find('.Compose_attacher') });
        _SetEvents();
        _FillNewFields(showOpts);
        return defer.promise();
    }

    THIS.load = function() {
        var defer = $.Deferred();
        ( $('#Compose_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.LoadTemplate('WidgetCompose.html')
        ).done(function() {
            $.when(
                Dialog.Load(),
                TextBadges.Load(),
                ContactsAutocomplete.Load(),
                WidgetAttacher.Load()
            ).done(function() {
                defer.resolve();
            });
        });
        return defer.promise();
    };

    THIS.show = function(showOptions) {
        var showOpts = $.extend({
            forward: null, // headline object; this email is a forwarding
            reply: null, // a replying
            replyToAll: null, // a reply-to-all
            draft: null // a draft editing
        }, showOptions);

        if (isSending) { // "send" asynchronous request is running right now
            window.alert('Um email está sendo enviado, aguarde.');
        } else if (popup !== null && popup.isOpen()) { // popup is already open with another message
            if (popup.isMinimized()) {
                popup.toggleMinimize(); // if there's a popup active, just restore it
            }
            if (_UserWroteSomethingNew()) {
                if (window.confirm('Há um email sendo escrito que ainda não foi enviado.\n' +
                    'Deseja descartá-lo?')) {
                    popup.close().done(function() {
                        _CreateNewDialog(showOpts);
                    });
                }
            } else { // close current message, since user wrote nothing new
                popup.close().done(function() {
                    _CreateNewDialog(showOpts);
                });
            }
        } else { // a fresh, new window
            _CreateNewDialog(showOpts);
        }

        return THIS;
    };

    THIS.onClose = function(callback) {
        onCloseCB = callback; // onClose()
        return THIS;
    };

    THIS.onSend = function(callback) {
        onSendCB = callback;
        return THIS;
    };

    THIS.onDraft = function(callback) {
        onDraftCB = callback;
        return THIS;
    };
};
});
