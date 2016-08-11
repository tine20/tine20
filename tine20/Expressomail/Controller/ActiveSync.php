<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jeferson Miranda <jeferson.miranda@serpro.gov.br>
 * @copyright   Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 *
 */

/**
 * folder controller for Expressomail
 *
 * @package     Expressomail
 * @subpackage  Controller
 */
class Expressomail_Controller_ActiveSync extends Tinebase_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Expressomail';

    /**
     * holds the instance of the singleton
     *
     * @var Expressomail_Controller_ActiveSync
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {}

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * the singleton pattern
     *
     * @return Expressomail_Controller_ActiveSync
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressomail_Controller_ActiveSync();
        }

        return self::$_instance;
    }

    /************************************* public functions *************************************/

    /**
     * recover html body and atachment data from rfc822 mime
     *
     * @param string $mime
     * @return array
     */
    public function getHtmlBodyAndAttachmentData($mime, $onlyInline = FALSE)
    {
        //$filepath = '../tests/tine20/Expressomail/Frontend/files/mime/';
        //$filename = microtime();
        //file_put_contents($filepath.$filename.'.eml', $mime);

        preg_match('/.*boundary="(.*)"/', $mime, $matches);
        if (count($matches) === 0) {
            Zend_Mime_Decode::splitMessage($mime, $headers, $body);
            $body = base64_decode($body);
            if (strpos($headers['content-type'], 'text/plain') !== FALSE) {
                $body = $this->convertPlainTextToHtml($body);
            }
            $result = array(
                'body' => $body,
            );
            //file_put_contents($filename.'.json', json_encode($result));
            return $result;
        }
        $struct = Zend_Mime_Decode::splitMessageStruct($mime, $matches[1]);

        $result = array();
        $result['attachmentData'] = array();

        $multipartContent = $this->getMultipartPart($struct, 'multipart');
        if ($multipartContent) {
            preg_match('/.*boundary="(.*)"/', $multipartContent['header']['content-type'], $matches);
            $bodyBoundary = $matches[1];
            $bodyStruct = Zend_Mime_Decode::splitMessageStruct($multipartContent['body'], $bodyBoundary);
            $bodyContent = $this->getMultipartPart($bodyStruct, 'text/html');
            if ($bodyContent) {
                $bodyHtml = $this->extractBodyTagContent($bodyContent['body']);
                if (strpos($bodyContent['header']['content-transfer-encoding'], 'base64') !== FALSE) {
                    $bodyHtml = base64_decode($bodyHtml);
                }
            } else {
                $bodyHtml = '';
            }
            $result['attachmentData'] = $this->getAttachmentParts($bodyStruct, $onlyInline);
        } else {
            $bodyContent = $this->getMultipartPart($struct, 'text/html');
            if ($bodyContent) {
                if (strpos($bodyContent['header']['content-transfer-encoding'], 'base64') !== FALSE) {
                    $bodyHtml = base64_decode($bodyContent['body']);
                } else {
                    $bodyHtml = $this->extractBodyTagContent($bodyContent['body']);
                }
            } else {
                $bodyContent = $this->getMultipartPart($struct, 'text');
                $bodyHtml = $bodyContent['body'];
                if (strpos($bodyContent['header']['content-transfer-encoding'], 'base64') !== FALSE) {
                    $bodyHtml = base64_decode($bodyHtml);
                }
                if (strpos($bodyContent['header']['content-type'], 'text/plain') !== FALSE) {
                    $bodyHtml = $this->convertPlainTextToHtml($bodyHtml);
                }
            }
        }
        $result['body'] = quoted_printable_decode($bodyHtml);

        $result['attachmentData'] = array_merge($result['attachmentData'], $this->getAttachmentParts($struct, $onlyInline));

        //$filepath = '../tests/tine20/Expressomail/Frontend/files/result/';
        //file_put_contents($filepath.$filename.'.json', json_encode($result));

        return $result;
    }

    /**
     * replace new lines charactes with <br /> tag
     *
     * @param string $text
     * @return string
     */
    public function convertPlainTextToHtml($text) {
        return str_replace(array('\r\n', '\r', '\n', PHP_EOL), '<br />', $text);
    }

    /**
     * extract body content from html
     *
     * @param string $body
     * @return string
     */
    public function extractBodyTagContent($body) {
        $bodyContent = '';
        preg_match('/<body(.*)>/iUms', $body, $matches);
        if (count($matches) > 0) {
            preg_match('/'.$matches[0].'(.*)<\/body>/iUms', $body, $matches);
            $bodyContent = count($matches) == 0 ? $body : count($matches) > 1 ? $matches[1] : $matches[0];
        } else {
            $bodyContent = $body;
        }
        return $body;
    }

    /**
     * get array index of multipart mime, if exists
     *
     * @param array $struct
     * @param string $partType
     * @return array
     */
    public function getMultipartPart($struct, $partType)
    {
        foreach($struct as $part) {
            if (strpos($part['header']['content-type'], $partType) !== FALSE) {
                return $part;
            }
        }
        return null;
    }

    /**
     * get attachment parts
     *
     * @param array $struct
     * @return array
     */
    public function getAttachmentParts($struct, $onlyInline)
    {
        $attachmentData = array();
        foreach($struct as $part) {
            if (strpos($part['header']['content-type'], 'text') !== FALSE ||
                strpos($part['header']['content-type'], 'multipart') !== FALSE) {
                continue;
            }
            if ($onlyInline && strpos($part['header']['content-disposition'], 'inline') === FALSE) {
                continue;
            }
            preg_match('/.*name="(.*)"/', $part['header']['content-type'], $matches);
            $name = (isset($matches[1]) ? $matches[1] : FALSE);
            if (!$name) {
                preg_match('/.*filename="(.*)"/', $part['header']['content-disposition'], $matches);
                $name = $matches[1];
            }
            preg_match('/.+?(?=;)/', $part['header']['content-type'], $matches);
            $type = (isset($matches[0]) ? $matches[0] : FALSE);
            if (!$type) {
                $type = $part['header']['content-type'];
            }
            $attData = array(
                'name' => $name,
                'type' => $type,
                'content' => $part['body'],
            );
            array_push($attachmentData, $attData);
        }
        return $attachmentData;
    }

    /**
     * add attachents to Zend_Mail object
     *
     * @param Zend_Mail $mail
     * @param array $attchmentData
     */
    public function addAttachments($mail, $attchmentData)
    {
        foreach ($attchmentData as $attData) {
            $mail->createAttachment(
                base64_decode($attData['content']),
                $attData['type'],
                Zend_Mime::DISPOSITION_INLINE,
                Zend_Mime::ENCODING_BASE64,
                $attData['name']);
        }
    }

}
