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
use Hfig\MAPI\Mime\Swiftmailer\Message as BaseMessage;

class Felamimail_MAPI_Message extends BaseMessage
{
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
