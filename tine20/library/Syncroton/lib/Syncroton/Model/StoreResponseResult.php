<?php
/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Search/Response/Store/Result elements
 *
 * @package     Syncroton
 * @subpackage  Model
 */
class Syncroton_Model_StoreResponseResult extends Syncroton_Model_AEntry
{
    protected $_xmlBaseElement = 'Result';

    protected $_properties = array(
        'AirSync' => array(
            'class'        => array('type' => 'string'),
            'collectionId' => array('type' => 'string'),
        ),
        'Search' => array(
            'longId'     => array('type' => 'string'),
            'properties' => array('type' => 'container'),
        )
    );
}
