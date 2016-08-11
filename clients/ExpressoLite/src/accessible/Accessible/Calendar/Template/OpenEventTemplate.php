<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Displays a calendar event information.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/general.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Calendar/Template/OpenEventTemplate.css" />
    <title>Exibição de evento - ExpressoBr Acessível</title>
</head>
<body>

<div id="top">
    <div id="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="#eventInfo" accesskey="1">Ir para informações do evento [1]</a></li>
                <li><a href="#eventDescription" accesskey="2">Ir para descrição do evento [2]</a></li>
                <li><a href="#eventPartners" accesskey="3">Ir para participantes do evento [3]</a></li>
                <?php IF($VIEW->isUserAllowedToConfirm) : ?>
                    <li><a href="#eventActions" accesskey="4">Ir para ações de evento [4]</a></li>
                <?php ENDIF; ?>
                <li><a href="<?= $VIEW->lnkBackToCalendar ?>" accesskey="v">Voltar para o calendário [v]</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="eventInfo">
    <h2 class="anchorsTitle">Informações do evento</h2>
    <div class="contentAlign">
        <div><span class="fieldName">Assunto:     </span><?= $VIEW->summary ?></div>
        <div><span class="fieldName">Data:        </span><?= $VIEW->date ?></div>
        <div><span class="fieldName">Horário:     </span><?= $VIEW->schedule ?> horas.</div>
        <div><span class="fieldName">Local:       </span><?= $VIEW->location ?> </div>
        <div>
            <span class="fieldName">Organizador:  </span><?= $VIEW->organizerName ?>,
            <span class="adjustOrgUnitRegion"><?= $VIEW->organizerOrgUnitRegion ?></span>
        </div>
        <div><span class="fieldName">Participantes: </span> <?= $VIEW->countAttendees ?></div>
    </div>
</div>

<!--
 * This element is a top page anchor link, it can be repeated on template files.
 * But only one link element, of these repeated, should contain 'accesskey="t"' attribute.
 -->
<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top" accesskey="t">voltar ao topo [t]</a></li>
    </ul>
</div>

<div id="eventDescription">
    <h2 class="anchorsTitle">Descrição do evento</h2>
    <div class="contentAlign"><span class="fieldName"></span> <?= $VIEW->description ?></div>
</div>

<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>

<div id="eventPartners">
    <h2 class="anchorsTitle">Participantes do evento</h2>
    <div class="contentAlign">
    <?php FOREACH($VIEW->attendeesInformation AS $confirmType) : ?>
        <?php FOREACH($confirmType AS $attendee) :?>
            <div class="attendeeSeparator">
                <?= $attendee->name ?> <span class="confirmStatus">( <?= $attendee->confirmStatus ?> )</span>,
                <span class="adjustOrgUnitRegion"><?= $attendee->orgUnit ?><?= $attendee->region ?></span>
            </div>
        <?php ENDFOREACH; ?>
    <?php ENDFOREACH; ?>
    </div>
</div>

<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>

<?php IF($VIEW->isUserAllowedToConfirm) : ?>
<div id="eventActions">
    <h2 class="anchorsTitle">Ações de evento</h2>
    <div class="links linkAsButton">
        <ul>
            <li><a href="<?= $VIEW->lnkTentative ?>">Tentar comparecer</a></li>
            <li><a href="<?= $VIEW->lnkAccepted ?>">Confirmar participação</a></li>
            <li><a href="<?= $VIEW->lnkDeclined ?>">Rejeitar</a></li>
            <li><a href="<?= $VIEW->lnkNeedsAction ?>">Responder depois</a></li>
        </ul>
    </div>
</div>

<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>
<?php ENDIF; ?>

</body>
</html>
