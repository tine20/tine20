<?php
/**
 * Tine 2.0
 *
 * MAPI message resolving class
 * - fix recipient header parsing problem
 *
 * @package     Felamimail
 * @subpackage  MAPI
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Hfig\MAPI\Mime\HeaderCollection;
use Hfig\MAPI\Mime\Swiftmailer\Adapter\DependencySet;
use Hfig\MAPI\Mime\Swiftmailer\Attachment;
use Hfig\MAPI\Mime\Swiftmailer\Message as BaseMessage;

class Felamimail_MAPI_Message extends BaseMessage
{
    public function toMime()
    {
        DependencySet::register();

        $message = new \Swift_Message();
        $message->setEncoder(new \Swift_Mime_ContentEncoder_RawContentEncoder());


        // get headers
        $headers = $this->translatePropertyHeaders();

        // add them to the message
        $add = [$message, 'setTo']; // function
        $this->addRecipientHeaders('To', $headers, $add);
        $headers->unset('To');

        $add = [$message, 'setCc']; // function
        $this->addRecipientHeaders('Cc', $headers, $add);
        $headers->unset('Cc');

        $add = [$message, 'setBcc']; // function
        $this->addRecipientHeaders('Bcc', $headers, $add);
        $headers->unset('Bcc');

        $add = [$message, 'setFrom']; // function
        $this->addRecipientHeaders('From', $headers, $add);
        $headers->unset('From');

        // skip parsing invalid message id
        $id = $headers->getValue('Message-ID');
        if ($id) {
            $message->setId(trim($id, '<>'));
        }
        $message->setDate(new \DateTime($headers->getValue('Date')));
        if ($boundary = $this->getMimeBoundary($headers)) {
            $message->setBoundary($boundary);
        }


        $headers->unset('Message-ID');
        $headers->unset('Date');
        $headers->unset('Mime-Version');
        $headers->unset('Content-Type');

        $add = [$message->getHeaders(), 'addTextHeader'];
        $this->addPlainHeaders($headers, $add);


        // body
        $hasHtml = false;
        $bodyBoundary = '';
        if ($boundary) {
            if (preg_match('~^_(\d\d\d)_([^_]+)_~', $boundary, $matches)) {
                $bodyBoundary = sprintf('_%03d_%s_', (int)$matches[1]+1, $matches[2]);
            }
        }
        try {
            $html = $this->getBodyHTML();
            if ($html) {
                $hasHtml = true;
                // build multi-part
                // (simple method is to just call addPart() on message but we can't control the ID
                $multipart = new \Swift_Attachment();
                $multipart->setContentType('multipart/alternative');
                $multipart->setEncoder($message->getEncoder());
                if ($bodyBoundary) {
                    $multipart->setBoundary($bodyBoundary);
                }
                $multipart->setBody($this->getBody(), 'text/plain');

                $part = new \Swift_MimePart($html, 'text/html', null);
                $part->setEncoder($message->getEncoder());


                $message->attach($multipart);
                $multipart->setChildren(array_merge($multipart->getChildren(), [$part]));
            } else {
                $message->setBody($this->getBody(), 'text/plain');
            }
        } catch (\Exception $e) {
            // ignore invalid HTML body
        }
        
        // attachments
        foreach ($this->getAttachments() as $a) {
            $wa = Attachment::wrap($a);
            $attachment = $wa->toMime();

            $message->attach($attachment);
        }

        return $message;
    }
    
    protected function translatePropertyHeaders()
    {
        $rawHeaders = new HeaderCollection();

        // additional headers - they can be multiple lines
        $transport = [];
        $transportKey = 0;

        $transportRaw = explode("\r\n", $this->properties['transport_message_headers']);
        foreach ($transportRaw as $v) {
            if (!$v) continue;

            if ($v[0] !== "\t" && $v[0] !== ' ') {
                $transportKey++;
                $transport[$transportKey] = $v;
            }
            else {
                $transport[$transportKey] = $transport[$transportKey] . "\r\n" . $v;
            }
        }

        foreach ($transport as $header) {
            $rawHeaders->add($header);
        }
        
        // sender        
        $senderType = $this->properties['sender_addrtype'];
        if ($senderType == 'SMTP') {
            $rawHeaders->set('From', $this->getSender());
        }
        elseif (!$rawHeaders->has('From')) {
            if ($from = $this->getSender()) {
                $rawHeaders->set('From', $from);
            }
        }
        
        // recipients
        $recipients = $this->getRecipients();
        
        // fix duplicated content
        foreach (['to', 'cc', 'bcc'] as $type) {
            $rawHeaders->unset($type);
        }
        
        foreach ($recipients as $r) {
            $rawHeaders->add($r->getType(), (string)$r);
        }
        
        // subject - preference to msg properties
        if ($this->properties['subject']) {
            $rawHeaders->set('Subject', $this->properties['subject']);
        }

        // date - preference to transport headers
        if (!$rawHeaders->has('Date')) {
            $date = $this->properties['message_delivery_time'] ?? $this->properties['client_submit_time']
                ?? $this->properties['last_modification_time'] ?? $this->properties['creation_time'] ?? null;
            if (!is_null($date)) {
                // ruby-msg suggests this is stored as an iso8601 timestamp in the message properties, not a Windows timestamp
                $date = date('r', strtotime($date));
                $rawHeaders->set('Date', $date);
            }
        }

        // other headers map
        $map = [
            ['internet_message_id', 'Message-ID'],
            ['in_reply_to_id',      'In-Reply-To'],

            ['importance',          'Importance',  function($val) { return ($val == '1') ? null : $val; }],
            ['priority',            'Priority',    function($val) { return ($val == '1') ? null : $val; }],
            ['sensitivity',         'Sensitivity', function($val) { return ($val == '0') ? null : $val; }],

            ['conversation_topic',  'Thread-Topic'],

            //# not sure of the distinction here
            //# :originator_delivery_report_requested ??
            ['read_receipt_requested', 'Disposition-Notification-To', function($val) use ($rawHeaders) {
                $from = $rawHeaders->getValue('From');

                if (preg_match('/^((?:"[^"]*")|.+) (<.+>)$/', $from, $matches)) {
                    $from = trim($matches[2], '<>');
                }
                return $from;
            }]
        ];
        foreach ($map as $do) {
            $value = $this->properties[$do[0]];
            if (isset($do[2])) {
                $value = $do[2]($value);
            }
            if (!is_null($value)) {
                $rawHeaders->set($do[1], $value);
            }
        }

        return $rawHeaders;

    }
}
