<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry index page for calendar module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/general.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Calendar/Template/MainTemplate.css" />
    <title> <?= $VIEW->calendarMainTitle ?>- ExpressoBr Acessível</title>
</head>
<body>

<div id="top" name="top" >
    <div id="anchors" name="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="#menu" accesskey="1">Ir para o menu [1]</a></li>
                <?php IF($VIEW->isTodayExhibition) : ?>
                    <li><a href="#dayEvents" accesskey="2">Ir para eventos de hoje [2]</a></li>
                <?php ENDIF; ?>
                <li><a href="#monthEvents" accesskey="3">Ir para eventos do mês [3]</a></li>
                <li><a href="#monthNavigation" accesskey="4">Ir para navegação por mês [4]</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="menu" name="menu">
    <h2 class="anchorsTitle">Menu</h2>
    <div class="links systemLinks">
        <ul>
            <li><a href="<?= $VIEW->lnkEmail ?>" accesskey="e">Módulo Email [e]</a></li>
            <li><a href="<?= $VIEW->lnkChangeCalendar ?>" accesskey="q">Selecionar calendário [q]</a></li>
            <li><a href="<?= $VIEW->lnkLogoff ?>" title="Sair do expressobr acessível" accesskey="s">Sair do sistema [s]</a></li>
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

<?php IF($VIEW->isTodayExhibition) : ?>
<div id="dayEvents" name="dayEvents">
    <h2 class="anchorsTitle">Eventos de hoje</h2>
    <?php IF($VIEW->hasTodayEvents) : ?>
    <table id="dailyEventsTable" name="dailyEventsTable" class="clickableCell contentAlign eventsTable">
        <caption><?= $VIEW->dateRangeTodayEventsSummary; ?></caption>
        <thead>
            <tr>
                <th id="sequenceToday" class="adjustSeq" aria-hidden="true">#</th>
                <th id="horario" class="adjustCol">Horário</th>
                <th id="subject">Eventos</th>
            </tr>
        </thead>
        <tbody>

            <?php $SEQ = 1; ?>
            <?php FOREACH ($VIEW->todayEventListing AS $EVENT) : ?>
            <tr class="<?= $EVENT->notYetOccurred ? 'notYetOccurred' : '' ?>">
                <td headers="sequenceToday" class="alignCenter" aria-hidden="true"><?= $SEQ; ?></td>
                <td headers="date" class="alignCenter">
                    <?= $EVENT->formattedFrom ?> às <?= $EVENT->formattedUntil ?>
                </td>
                <td headers="subject" class="alignLeft">
                    <a href="<?= $EVENT->lnkOpenEvent ?>" title="Visualizar este evento">
                        <span class="onlyForScreenReaders">
                            Evento de hoje <?= $SEQ; ?>,
                            Assunto: <?= $EVENT->summary; ?>,
                            Horário: <?= $EVENT->formattedFrom; ?> às <?= $EVENT->formattedUntil; ?>
                        </span>
                        <span aria-hidden="true"><?= $EVENT->summary; ?></span>
                    </a>
                </td>
            </tr>
            <?php $SEQ++; ?>
            <?php ENDFOREACH; ?>
        </tbody>
    </table>
    <?php ELSE : ?>
        <p class="alignCenter emptyEvents"> <?= $VIEW->dateRangeTodayEventsSummary; ?> </p>
    <?php ENDIF; ?>
</div>

<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>
<?php ENDIF; ?>

<div id="monthEvents" name="monthEvents">
    <h2 class="anchorsTitle">Eventos do mês</h2>
    <?php IF($VIEW->hasEvents) : ?>
    <table id="monthlyEventsTable" name="monthlyEventsTable" class="clickableCell contentAlign eventsTable">
        <caption><?= $VIEW->dateRangeEventsSummary ?></caption>
        <thead>
            <tr>
                <th id="sequence" class="adjustSeq" aria-hidden="true">#</th>
                <th id="date" class="adjustCol">Data</th>
                <th id="subject">Eventos</th>

            </tr>
        </thead>
        <tbody>
            <?php $SEQ = 1; ?>
            <?php FOREACH ($VIEW->eventListing AS $EVENT) : ?>
            <tr class="<?= $EVENT->notYetOccurred ? 'notYetOccurred' : '' ?>">
                <td headers="sequence" class="alignCenter" aria-hidden="true"><?= $SEQ; ?></td>
                <td headers="date" class="alignCenter">
                    <?= $EVENT->formattedWeekDay ?>,
                    <?= $EVENT->formattedDay ?> de
                    <?= $EVENT->formattedMonth ?>
                    <?= $EVENT->formattedFrom; ?>
                </td>
                <td headers="subject" class="alignLeft">
                    <a href="<?= $EVENT->lnkOpenEvent ?>" title="Visualizar este evento">
                        <span class="onlyForScreenReaders">
                            Evento <?= $SEQ; ?>,
                            Assunto: <?= $EVENT->summary; ?>,
                            <?= $EVENT->formattedWeekDay ?>,
                            <?= $EVENT->formattedDay ?> de <?= $EVENT->formattedMonth ?>
                        </span>
                        <span aria-hidden="true"><?= $EVENT->summary; ?></span>
                    </a>
                </td>
            </tr>
            <?php $SEQ++; ?>
            <?php ENDFOREACH; ?>
        </tbody>
    </table>
    <?php ELSE : ?>
        <p class="alignCenter emptyEvents"> <?= $VIEW->dateRangeEventsSummary ?> </p>
    <?php ENDIF ?>

    <div class="backToTop links systemLinks contentAlign">
        <ul>
            <li><a href="#top">voltar ao topo [t]</a></li>
        </ul>
    </div>
</div>

<div id="monthNavigation" name="monthNavigation">
    <h2 class="anchorsTitle">Navegação por mês</h2>
    <div class="links linkAsButton alignCenter">
        <ul>
            <?php FOREACH ($VIEW->calendarNavigation AS $MONTH) : ?>
                <li><a href="<?= $MONTH->lnk ?>" title="<?= $MONTH->lnkTitle ?>"><?= $MONTH->lnkText ?></a></li>
            <?php ENDFOREACH; ?>
        </ul>
    </div>
</div>

<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>

</body>
</html>