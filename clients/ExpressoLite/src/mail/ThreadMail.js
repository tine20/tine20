/*!
 * Expresso Lite
 * Groups a linear array of emails into conversations; also other related operations.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

define([], function() {
    var ThreadMail = { };

    function _UniqueSenders(threads, useAddressee) {
        var senders = []; // unique senders present in all email threads given
        for (var t = 0; t < threads.length; ++t) { // each thread is an array of emails
            for (var m = 0; m < threads[t].length; ++m) { // each email in thread
                var mail = threads[t][m];
                if (useAddressee) { // usually for a "Sent" folder
                    for (var i = 0; i < mail.to.length; ++i) {
                        var to = mail.to[i];
                        var alreadyAdded = false;
                        for (var s = 0; s < senders.length; ++s) {
                            if (to.toLowerCase() === senders[s].email.toLowerCase()) {
                                alreadyAdded = true;
                                break;
                            }
                        }
                        if (!alreadyAdded) {
                            senders.push({
                                name: to.split('@')[0],
                                email: to,
                                threads: [], // we won't populate it here
                                unreads: 0
                            });
                        }
                    }
                } else { // ordinary, not a "Sent" folder
                    var alreadyAdded = false;
                    for (var s = 0; s < senders.length; ++s) {
                        if (mail.from.email.toLowerCase() === senders[s].email.toLowerCase()) {
                            alreadyAdded = true;
                            break;
                        }
                    }
                    if (!alreadyAdded) {
                        senders.push({
                            name: mail.from.name,
                            email: mail.from.email,
                            threads: [], // we won't populate it here
                            unreads: 0
                        });
                    }
                }
            }
        }
        return senders.sort(function(a, b) {
            return a.name.localeCompare(b.name); // alphabetically by name
        });
    }

    function _CountUnread(arrMails) {
        var count = 0;
        for (var i = 0; i < arrMails.length; ++i) {
            if (arrMails[i].unread) {
                ++count;
            }
        }
        return count;
    }

    ThreadMail.GroupBySender = function(threads) {
        var senders = _UniqueSenders(threads, false);
        for (var s = 0; s < senders.length; ++s) {
            for (var t = 0; t < threads.length; ++t) { // each thread is an array of emails
                var wasAdded = false;
                for (var m = 0; m < threads[t].length; ++m) { // each email in thread
                    var mail = threads[t][m];
                    if (mail.from.email.toLowerCase() === senders[s].email.toLowerCase()) { // sender participated of this thread
                        senders[s].threads.push(threads[t]);
                        wasAdded = true;
                        break;
                    }
                }
                if (wasAdded && _CountUnread(threads[t])) {
                    ++senders[s].unreads;
                }
            }
        }
        return senders;
    };

    ThreadMail.GroupByAddressee = function(threads) {
        var addressees = _UniqueSenders(threads, true);
        for (var a = 0; a < addressees.length; ++a) {
            var addressee = addressees[a];
            for (var t = 0; t < threads.length; ++t) { // each thread is an array of emails
                var wasAdded = false;
                for (var m = 0; m < threads[t].length; ++m) { // each email in thread
                    var mail = threads[t][m];
                    for (i = 0; i < mail.to.length; ++i) {
                        var to = mail.to[i];
                        if (to.toLowerCase() === addressee.email.toLowerCase()) { // addressee participated of this thread
                            addressee.threads.push(threads[t]);
                            wasAdded = true;
                            break;
                        }
                    }
                    if (wasAdded) break;
                }
                if (wasAdded && _CountUnread(threads[t])) {
                    ++addressee.unreads;
                }
            }
        }
        return addressees;
    };

    ThreadMail.MakeThreads = function(headlines) {
        var threads = [];
        for (var h = 0; h < headlines.length; ++h) {
            headlines[h].subject2 = headlines[h].subject
                .replace(/(auto: )|(enc: )|(fw: )|(fwd: )|(re: )|(res: )/gi, 'FWD: '); // everything becomes "FWD:"
        }
        headlines.reverse(); // now sorted oldest first

        var threads = []; // each thread will be a flat array of headlines; if no thread, an array of 1 headline
        HEADS: for (var h = 0; h < headlines.length; ++h) {
            for (var t = 0; t < threads.length; ++t) {
                var canBeThreaded = headlines[h].subject2.indexOf('FWD: ') > -1;
                var hasSameSubject = headlines[h].subject2.replace(/^(FWD: )+/, '') ===
                    threads[t][0].subject2.replace(/^(FWD: )+/, '');
                if (canBeThreaded && hasSameSubject) {
                    threads[t].push(headlines[h]); // push into thread
                    continue HEADS;
                }
            }
            threads.push([ headlines[h] ]); // new thread with 1 headline
        }

        for (var t = 0; t < threads.length; ++t) {
            for (var i = 0; i < threads[t].length; ++i) {
                delete threads[t][i].subject2; // normalized subject was added just for our internal processing
            }
            threads[t].reverse(); // thread now sorted newest first
        }

        threads.sort(function(a, b) { // now all threads sorted newest first
            return b[0].received.getTime() - a[0].received.getTime(); // compare timestamps
        });

        headlines.reverse(); // sorted newest first, as it came to us
        return threads;
    };

    ThreadMail.MakeThreadsWithSingleMessage = function(headlines) {
        // Draft and search headlines are not grouped into threads, so return arrays with 1 message.
        var threads = [];
        for (var i = 0; i < headlines.length; ++i) {
            threads.push([ headlines[i] ]);
        }
        return threads;
    };

    ThreadMail.Merge = function(base, more) {
        var toBeJoined = [];
        for (var m = 0; m < more.length; ++m) { // array of messages
            var existent = false;
            for (var b = 0; b < base.length; ++b) { // array of messages
                if (more[m].id === base[b].id) {
                    existent = true;
                    break;
                }
            }
            if (!existent) toBeJoined.push(more[m]);
        }
        if (toBeJoined.length) {
            base.push.apply(base, toBeJoined);
            base.sort(function(a, b) { // newest messages first
                return b.received.getTime() - a.received.getTime(); // compare timestamps
            });
        }
        return base; // new messages merged into first array
    };

    ThreadMail.FindThread = function(threads, headline) {
        for (var i = 0; i < threads.length; ++i) {
            for (var j = 0; j < threads[i].length; ++j) { // a thread is an array of headlines
                if (threads[i][j].id === headline.id) {
                    return threads[i];
                }
            }
        }
        return null;
    };

    ThreadMail.RemoveHeadlinesFromFolder = function(msgIds, folder) {
        for (var i = folder.messages.length; i-- > 0; ) {
            if (msgIds.indexOf(folder.messages[i].id) !== -1) {
                folder.messages.splice(i, 1); // remove headline from flat headlines array of folder
            }
        }
        for (var i = folder.threads.length; i-- > 0; ) {
            var thread = folder.threads[i]; // a thread is an array of headlines
            for (var j = thread.length; j-- > 0; ) {
                if (msgIds.indexOf(thread[j].id) !== -1) {
                    thread.splice(j, 1); // remove headline from thread
                }
            }
            if (!thread.length) { // thread is empty now, remove it from folder
                folder.threads.splice(i, 1);
            }
        }
    };

    ThreadMail.FindFolderByGlobalName = function(name, folderCache) {
        for (var i = 0; i < folderCache.length; ++i) { // the global array with all top folders
            if (folderCache[i].globalName === name) {
                return folderCache[i];
            } else if (folderCache[i].subfolders.length) {
                var folder = ThreadMail.FindFolderByGlobalName(name, folderCache[i].subfolders);
                if (folder !== null) {
                    return folder;
                }
            }
        }
        return null;
    };

    ThreadMail.ParseTimestamps = function(arrMails) {
        for (var i = 0; i < arrMails.length; ++i) {
            arrMails[i].received = new Date(arrMails[i].received * 1000);
        }
        return arrMails;
    };

    ThreadMail.FormatBytes = function(bytes) {
        if (typeof bytes !== 'number') {
            bytes = parseInt(bytes);
        }
        if (bytes < 100) {
            return bytes + ' bytes';
        } else if (bytes < 1024 * 200) {
            return (bytes / 1024).toFixed(1).replace('.', ',')+' KB';
        }
        return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',')+' MB';
    };

    return ThreadMail;
});
