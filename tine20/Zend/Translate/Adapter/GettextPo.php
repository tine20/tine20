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
 * @package    Zend_Translate
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: Gettext.php 10020 2009-08-18 14:34:09Z j.fischer@metaways.de $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/** Zend_Locale */
require_once 'Zend/Locale.php';

/** Zend_Translate_Adapter */
require_once 'Zend/Translate/Adapter.php';

/**
 * @category   Zend
 * @package    Zend_Translate
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Translate_Adapter_GettextPo extends Zend_Translate_Adapter {
    // Internal variables
    private $_file        = false;
    private $_adapterInfo = array();
    private $_data        = array();

    /**
     * Generates the  adapter
     *
     * @param  string              $data     Translation data
     * @param  string|Zend_Locale  $locale   OPTIONAL Locale/Language to set, identical with locale identifier,
     *                                       see Zend_Locale for more information
     * @param  array               $options  OPTIONAL Options to set
     */
    public function __construct($data, $locale = null, array $options = array())
    {
        parent::__construct($data, $locale, $options);
    }


    /**
     * Load translation data (PO file reader)
     *
     * @param  string  $filename  PO file to add, full path must be given for access
     * @param  string  $locale    New Locale/Language to set, identical with locale identifier,
     *                            see Zend_Locale for more information
     * @param  array   $option    OPTIONAL Options to use
     * @throws Zend_Translation_Exception
     * @return array
     */
     protected function _loadTranslationData($filename, $locale, array $options = array())
    {
    
        //Ignor files except .po
        if (!preg_match('/\.po$/', $filename)) {
            return array();
        }
        
        $this->_data      = array();
        $this->_file      = @fopen($filename, 'rb');
        $header = "";
        $id = "";
        $str = "";
        
        //Exception: Can't open file.
        if (!$this->_file) {
            require_once 'Zend/Translate/Exception.php';
            throw new Zend_Translate_Exception('Error opening translation file \'' . $filename . '\'.');
        }
        //Writes Header into _adapterInfo
        while ($line = fgets($this->_file)) {
            if ($line == "\n" || $line == "\r\n") {
                break;
             }
            if (strpos($line, '"')=== 0){
                $header = $header . "\n" . substr($line, 1, -4);
            }
        }
        if (empty($header)) {
            $this->_adapterInfo[$filename] = 'No adapter information available';
        } else {
            $this->_adapterInfo[$filename] = $header;
        }
        //Writes msgids and msgstr into _data
        while ($line = fgets($this->_file)) {
            // fix for wrong calculate lenght only for windows format (CRLF)
            $line = str_replace("\r\n", "\n", $line);
            if (strpos($line, 'msgid ')!== FALSE) {
                $id = substr($line, 7, -2);
                $line = fgets($this->_file);
                while (strpos($line, '"')=== 0){
                    $id = $id . substr($line, 1, -2);
                    $line = fgets($this->_file);
                }
                $this->_data[$locale][$id] = "";
            }
            if(strpos($line, 'msgstr "')!== FALSE){
                $line = str_replace("\r\n", "\n", $line);
                $str = substr($line, 8, -2);
                $line = fgets($this->_file);
                while (strpos($line, '"')=== 0){
                    $str = $str . substr($line, 1, -2);
                    $line = fgets($this->_file);
                }
                $this->_data[$locale][$id] = $str;
            }
            if(strpos($line, 'msgstr[')!== FALSE){
                $line = str_replace("\r\n", "\n", $line);    
                $str = substr($line, 11, -2);
                $line = fgets($this->_file);
                while (strpos($line, '"')=== 0){
                    $str = $str . substr($line, 1, -2);
                    $line = fgets($this->_file);
                }
                $this->_data[$locale][$id][] = $str;
                fseek($this->_file, - strlen($line), SEEK_CUR);
            }
        }
        fclose ($this->_file);
        ksort($this->_data[$locale]);
        return $this->_data;
    }


    /**
     * Returns the adapter informations
     *
     * @return array Each loaded adapter information as array value
     */
    public function getAdapterInfo()
    {
        return $this->_adapterInfo;
    }

    /**
     * Returns the adapter name
     *
     * @return string
     */
    public function toString()
    {
        return "GettextPo";
    }
}