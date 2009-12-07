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
 * @category   Zend
 * @package    Zend_Service
 * @subpackage Nominatim
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Nominatim_Result
{
    /**
     * The objects ID.
     *
     * @var string
     */
    public $placeId;

    /**
     * The objects lon
     *
     * @var string
     */
    public $lon;

    /**
     * The objects lat
     *
     * @var string
     */
    public $lat;

    /**
     * The osm type
     *
     * @var string
     */
    public $osmType;

    /**
     * The osm id
     *
     * @var string
     */
    public $osmId;

    /**
     * The display name
     *
     * @var string
     */
    public $displayName;

    /**
     * The class
     *
     * @var string
     */
    public $class;

    /**
     * The type
     *
     * @var string
     */
    public $type;

    /**
     * The license the photo is available under.
     *
     * @var string
     */
    public $license;

    /**
     * The date the photo was uploaded.
     *
     * @var string
     */
    public $dateupload;

    /**
     * The date the photo was taken.
     *
     * @var string
     */
    public $datetaken;

    /**
     * The screenname of the owner.
     *
     * @var string
     */
    public $ownername;

    /**
     * The server used in assembling icon URLs.
     *
     * @var string
     */
    public $iconserver;

    /**
     * Parse the Flickr Result
     *
     * @param  SimpleXMLElement    $place
     * @return void
     */
    public function __construct(SimpleXMLElement $place)
    {
        $this->placeId      = (string)$place['place_id'];
        $this->osmType      = (string)$place['osm_type'];
        $this->osmId        = (string)$place['osm_id'];
        $this->lon          = (string)$place['lon'];
        $this->lat          = (string)$place['lat'];
        $this->displayName  = (string)$place['display_name'];
        $this->class        = (string)$place['class'];
        $this->type         = (string)$place['type'];
    }
}
