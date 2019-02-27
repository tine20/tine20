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
    <Response xmlns="<?php echo $this->schema;?>">
        <?php if ($this->email) {?>
            <User>
                <DisplayName><?php echo $this->email;?></DisplayName>
                <EMailAddress><?php echo $this->email;?></EMailAddress>
            </User>
        <?php }?>
        <Action>
            <Settings>
                <Server>
                    <Type>MobileSync</Type>
                    <Url><?php echo $this->url;?></Url>
                    <Name><?php echo $this->serverName;?></Name>
                </Server>
            </Settings>
        </Action>
        <?php echo $this->account; ?>
    </Response>
</Autodiscover>