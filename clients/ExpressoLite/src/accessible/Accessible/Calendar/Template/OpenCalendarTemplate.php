<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry index page for calendar module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/general.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Calendar/Template/OpenCalendarTemplate.css" />
    <title> Seleção de calendários - ExpressoBr Acessível</title>
</head>
<body>

<div id="top">
    <div id="logomark"></div>
    <div id="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="#calendars" accesskey="1">Ir para listagem de calendários [1]</a></li>
                <li><a href="<?= $VIEW->lnkBack ?>" accesskey="v">Voltar para o calendário anterior [v]</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="calendars">
<h2 class="anchorsTitle">Calendários</h2>
    <div class="links systemLinks">
        <ul >
            <?php FOREACH ($VIEW->calendars AS $CALENDAR) : ?>
                <li><a href="<?= $CALENDAR->lnkOpenCalendar ?> " title="Selecionar <?= $CALENDAR->name ?>"><?= $CALENDAR->name ?></a></li>
            <?php ENDFOREACH ?>
        </ul>
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

</body>
</html>
