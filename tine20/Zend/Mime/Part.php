<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Mime
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Zend_Mime
 */
require_once 'Zend/Mime.php';

/**
 * Class representing a MIME part.
 *
 * @category   Zend
 * @package    Zend_Mime
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Mime_Part {

    public $type = Zend_Mime::TYPE_OCTETSTREAM;
    public $encoding = Zend_Mime::ENCODING_8BIT;
    public $id;
    public $disposition;
    public $filename;
    public $description;
    public $charset;
    public $boundary;
    public $location;
    public $language;
    protected $_content;
    protected $_isStream = false;
    protected $_decodeFilters = array();
    protected $_decodeFilterResources = array();

    /**
     * create a new Mime Part.
     * The (unencoded) content of the Part as passed
     * as a string or stream
     *
     * @param mixed $content  String or Stream containing the content
     */
    public function __construct($content)
    {
        $this->_content = $content;
        if (is_resource($content)) {
            $this->_isStream = true;
        }
    }

    /**
     * @todo setters/getters
     * @todo error checking for setting $type
     * @todo error checking for setting $encoding
     */

    /**
     * check if this part can be read as a stream.
     * if true, getEncodedStream can be called, otherwise
     * only getContent can be used to fetch the encoded
     * content of the part
     *
     * @return bool
     */
    public function isStream()
    {
        return $this->_isStream;
    }

    /**
     * if this was created with a stream, return a stream for
     * reading the content. very useful for large file attachments.
     *
     * @return stream
     * @throws Zend_Mime_Exception if not a stream or unable to append filter
     */
    public function getRawStream()
    {
        if (!$this->_isStream) {
            require_once 'Zend/Mime/Exception.php';
            throw new Zend_Mime_Exception('Attempt to get a stream from a string part');
        }

        return $this->_content;
    }
    
    /**
     * if this was created with a stream, return a filtered stream for
     * reading the content. very useful for large file attachments.
     *
     * @return stream
     * @throws Zend_Mime_Exception if not a stream or unable to append filter
     */
    public function getEncodedStream($EOL = Zend_Mime::LINEEND)
    {
        if (!$this->_isStream) {
            require_once 'Zend/Mime/Exception.php';
            throw new Zend_Mime_Exception('Attempt to get a stream from a string part');
        }

        switch ($this->encoding) {
            case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                $this->_appendFilterToStream('convert.quoted-printable-encode', array(
                    'line-length'      => 76,
                    'line-break-chars' => $EOL
                ));
                break;
            case Zend_Mime::ENCODING_BASE64:
                $this->_appendFilterToStream('convert.base64-encode', array(
                    'line-length'      => 76,
                    'line-break-chars' => $EOL
                ));
                break;
            default:
                require_once 'StreamFilter/StringReplace.php';
                $this->_appendFilterToStream('str.replace', array(
                    'search'    => "\x0d\x0a",
                    'replace'   => $EOL
                ));
        }
        return $this->_content;
    }
    
    /**
     * if this was created with a stream, return a filtered stream for
     * reading the content. very useful for large file attachments.
     *
     * @return stream
     * @throws Zend_Mime_Exception if not a stream or unable to append filter
     */
    public function getDecodedStream()
    {
        if (!$this->_isStream) {
            require_once 'Zend/Mime/Exception.php';
            throw new Zend_Mime_Exception('Attempt to get a stream from a string part');
        }
        
        switch ($this->encoding) {
            case Zend_Mime::ENCODING_QUOTEDPRINTABLE:
                $this->_appendFilterToStream('convert.quoted-printable-decode');
                break;
            case Zend_Mime::ENCODING_BASE64:
                $this->_appendFilterToStream('convert.base64-decode');
                break;
            default:
        }
        
        foreach ($this->_decodeFilters as $filter) {
            $this->_appendFilterToStream($filter);
        }
        
        return $this->_content;
    }
    
    /**
     * append filter to stream
     * 
     * @param string $_filterString
     * @param array $_params
     * @throws Zend_Mime_Exception
     */
    protected function _appendFilterToStream($_filterString, $_params = array())
    {
        $filter = stream_filter_append(
            $this->_content,
            $_filterString,
            STREAM_FILTER_READ,
            $_params
        );
        if (!is_resource($filter)) {
            require_once 'Zend/Mime/Exception.php';
            throw new Zend_Mime_Exception('Failed to append ' . $_filterString . ' filter');
        }
                
        $this->_decodeFilterResources[] = $filter;
    }
    
    /**
     * append another filter
     * 
     * @param  string  $filter
     */
    public function appendDecodeFilter($filter)
    {
        $this->_decodeFilters[] = $filter;
    }

    /**
     * reset filters and rewinds the stream
     */
    public function resetStream()
    {
        if (!$this->_isStream) {
            require_once 'Zend/Mime/Exception.php';
            throw new Zend_Mime_Exception('Attempt to reset the stream of a string part');
        }
        
        foreach ($this->_decodeFilterResources as $filter) {
            stream_filter_remove($filter);
        }
        $this->_decodeFilters = array();
        $this->_decodeFilterResources = array();
        
        rewind($this->_content);
    }
    
    /**
     * Get the Content of the current Mime Part in the given encoding.
     *
     * @param string $EOL
     * @return string
     */
    public function getContent($EOL = Zend_Mime::LINEEND)
    {
        if ($this->_isStream) {
            $result = stream_get_contents($this->getEncodedStream($EOL));
        } else {
            $result = Zend_Mime::encode($this->_content, $this->encoding, $EOL);

            if ($this->encoding !== Zend_Mime::ENCODING_QUOTEDPRINTABLE && $this->encoding !== Zend_Mime::ENCODING_BASE64) {
                // need to replace those \r\n with $EOL and we don't want to overwrite Zend_Mime
                $result = str_replace("\x0d\x0a", $EOL, $result);
            }
        }
        
        return $result;
    }
    
    /**
     * Get the Content of the current Mime Part in the given decoding.
     *
     * @return String
     */
    public function getDecodedContent()
    {
        if ($this->_isStream) {
            $result = stream_get_contents($this->getDecodedStream());
        } else {
            // Zend_Mime::decode not yet implemented
            $result = Zend_Mime::decode($this->_content, $this->encoding);
        }
        
        return $result;
    }
    
    /**
     * Create and return the array of headers for this MIME part
     *
     * @access public
     * @return array
     */
    public function getHeadersArray($EOL = Zend_Mime::LINEEND)
    {
        $headers = array();

        $contentType = $this->type;
        if ($this->charset) {
            $contentType .= '; charset=' . $this->charset;
        }

        if ($this->boundary) {
            $contentType .= ';' . $EOL
                          . " boundary=\"" . $this->boundary . '"';
        }

        $headers[] = array('Content-Type', $contentType);

        if ($this->encoding) {
            $headers[] = array('Content-Transfer-Encoding', $this->encoding);
        }

        if ($this->id) {
            $headers[]  = array('Content-ID', '<' . $this->id . '>');
        }

        if ($this->disposition) {
            $disposition = $this->disposition;
            if ($this->filename) {
                $disposition .= '; filename="' . $this->filename . '"';
            }
            $headers[] = array('Content-Disposition', $disposition);
        }

        if ($this->description) {
            $headers[] = array('Content-Description', $this->description);
        }

        if ($this->location) {
            $headers[] = array('Content-Location', $this->location);
        }

        if ($this->language){
            $headers[] = array('Content-Language', $this->language);
        }

        return $headers;
    }

    /**
     * Return the headers for this part as a string
     *
     * @return String
     */
    public function getHeaders($EOL = Zend_Mime::LINEEND)
    {
        $res = '';
        foreach ($this->getHeadersArray($EOL) as $header) {
            $res .= $header[0] . ': ' . $header[1] . $EOL;
        }

        return $res;
    }
    
    /**
     * decode mime part content
     * 
     * @return void
     */
    public function decodeContent()
    {
        $this->_content = $this->getDecodedStream();
    }
}
