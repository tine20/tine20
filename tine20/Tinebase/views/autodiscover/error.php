<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
?><?xml version="1.0" encoding="utf-8"?>
<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
    <Response>
        <Error Time="<?php echo date('H:i:s');?>" Id="<?php echo uniqid();?>">
            <ErrorCode>600</ErrorCode>
            <Message>Invalid Request</Message>
            <DebugData />
        </Error>
    </Response>
</Autodiscover>