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
        <Account>
            <AccountType>email</AccountType>
            <Action>settings</Action>
            <?php foreach ($this->protocols as $proto => $data) {?>
                <Protocol>
                    <Type><?php echo $proto;?></Type>
                    <?php foreach ($data as $key => $val) {
                        echo '<' . $key . '>' . $val . '</' . $key . '>' . PHP_EOL;
                    }?>
                </Protocol>
            <?php } ?>
        </Account>
    </Response>
</Autodiscover>