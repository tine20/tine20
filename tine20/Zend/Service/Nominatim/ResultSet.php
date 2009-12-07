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
 * @package    Zend_Service
 * @subpackage Nominatim
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @see Zend_Service_Nominatim_Result
 */
require_once 'Zend/Service/Nominatim/Result.php';


/**
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Nominatim
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Nominatim_ResultSet implements SeekableIterator,Countable
{
    /**
     * Total number of available results
     *
     * @var int
     */
    protected $_total = 0;

    /**
     * Number of results in this result set
     *
     * @var int
     */
    public $totalResultsReturned;

    /**
     * The offset of this result set in the total set of available results
     *
     * @var int
     */
    public $firstResultPosition;

    /**
     * Results storage
     *
     * @var SimpleXMLElement
     */
    protected $_results = null;

    /**
     * Current index for the Iterator
     *
     * @var int
     */
    private $_currentIndex = 0;

    /**
     * Parse the Nominatim Result Set
     *
     * @param  SimpleXMLElement $xml
     * @return void
     */
    public function __construct(SimpleXMLElement $xml)
    {
        $this->_results = $xml;
        $this->_total = count($xml->place);        
    }

    /**
     * Total Number of results returned
     *
     * @return int Total number of results returned
     */
    public function count()
    {
        return $this->_total;
    }

    /**
     * Implements SeekableIterator::current()
     *
     * @return Zend_Service_Flickr_Result
     */
    public function current()
    {
        return new Zend_Service_Nominatim_Result($this->_results->place[$this->_currentIndex]);
    }

    /**
     * Implements SeekableIterator::key()
     *
     * @return int
     */
    public function key()
    {
        return $this->_currentIndex;
    }

    /**
     * Implements SeekableIterator::next()
     *
     * @return void
     */
    public function next()
    {
        $this->_currentIndex += 1;
    }

    /**
     * Implements SeekableIterator::rewind()
     *
     * @return void
     */
    public function rewind()
    {
        $this->_currentIndex = 0;
    }

    /**
     * Implements SeekableIterator::seek()
     *
     * @param  int $index
     * @throws OutOfBoundsException
     * @return void
     */
    public function seek($index)
    {
        $indexInt = (int) $index;
        if ($indexInt >= 0 && (null === $this->_results || $indexInt < $this->_total)) {
            $this->_currentIndex = $indexInt;
        } else {
            throw new OutOfBoundsException("Illegal index '$index'");
        }
    }

    /**
     * Implements SeekableIterator::valid()
     *
     * @return boolean
     */
    public function valid()
    {
        return null !== $this->_results && $this->_currentIndex < $this->_total;
    }
}

