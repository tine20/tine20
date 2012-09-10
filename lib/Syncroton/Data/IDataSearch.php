<?php

/**
 * Syncroton
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2012 Kolab SYstems AG (http://www.kolabsys.com)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Aleksander Machniak <machniak@kolabsys.com>
 */

/**
 * class to handle ActiveSync Search command
 *
 * @package     Model
 */
interface Syncroton_Data_IDataSearch
{
    /**
     * Search command handler
     *
     * @param Syncroton_Model_StoreRequest $store   Search query parameters
     *
     * @return Syncroton_Model_StoreResponse
     */
    public function search(Syncroton_Model_StoreRequest $store);
}
