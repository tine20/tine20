<?php
/**
 * Tine 2.0
 *
 * MAPI Attachment resolving class
 * - skip parsing content id
 *
 * @package     Felamimail
 * @subpackage  MAPI
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Hfig\MAPI\Mime\MimeConvertible;
use Hfig\MAPI\Mime\Swiftmailer\Adapter\DependencySet;
use Hfig\MAPI\Mime\Swiftmailer\Attachment as BaseAttachment;


class Felamimail_MAPI_Attachment extends BaseAttachment
{
    public static function wrap(Hfig\MAPI\Message\Attachment $attachment)
    {
        if ($attachment instanceof MimeConvertible) {
            return $attachment;
        }

        return new self($attachment->obj, $attachment->parent);
    }

    public function toMime()
    {
        DependencySet::register();

        $attachment = new \Swift_Attachment();

        if ($this->getMimeType() != 'Microsoft Office Outlook Message') {
            $attachment->setFilename($this->getFilename());
            $attachment->setContentType($this->getMimeType());
        }
        else {
            $attachment->setFilename($this->getFilename() . '.eml');
            $attachment->setContentType('message/rfc822');
        }

        if ($data = $this->properties['attach_content_disposition']) {
            $attachment->setDisposition($data);
        }

        if ($data = $this->properties['attach_content_location']) {
            $attachment->getHeaders()->addTextHeader('Content-Location', $data);
        }

        // skip parsing invalid attachment id

        if ($this->embedded_msg) {
            $attachment->setBody(
                \Hfig\MAPI\Mime\Swiftmailer\Message::wrap($this->embedded_msg)->toMime()
            );
        }
        elseif ($this->embedded_ole) {
            // in practice this scenario doesn't seem to occur
            // MS Office documents are attached as files not
            // embedded ole objects
            throw new \Exception('Not implemented: saving emebed OLE content');
        }
        else {
            $attachment->setBody($this->getData());
        }

        return $attachment;
    }
}
