TimeZoneConverter - PHP Timezone Converter
==========================================

Library to convert PHP timezones from and into external representations like VTIMEZONE.

Supported Timezone Representations
----------------------------------

The following representations are supported.

* [VTtimeZone](http://tools.ietf.org/html/rfc5545#section-3.6.5) -- ICAL VTIMEZONE Components (RFC 5545)

VTimeZone
---------

### Convert VTIMEZONE to DateTimeZone

     (DateTimeZone) TimeZoneConvert::fromVTimeZone(string $VTimeZone [, string $prodId = "" [, mixed $expectedTimeZone = NULL]]); 

In the best case, the VTIMEZONE contains a well known timezone identifier
string the library can detect.  In this case $prodId and $expectedTimeZone
are not evaluated. If not, things become complicated.

A VTIMEZONE describes the rules which apply for the given timezone.
Unfortunally this rule is of no help in PHP timezone computations are
based on a DateTimeZone object which could represent one of the 
approximatly 400 build in timezones indentified by a timezone id which
is the timezone name of the ohlson timezone database.

As multiple timezones follow the same rules, it is not possible to compute
the described timezone precisely. For instance the rules for Europe/Berlin
are exactly the same as for Europe/Paris. Therefore its possible to pass
the optional $expectedTimeZone parameter to pick the appropriate timezone.

Some clients just send horrible VTIMEZONE components which don't have a
known timezone identifier and also don't have a correct definition.  For
these cases the library maintains a [ChamberOfHorrors](TimeZoneConvert/blob/master/lib/TimeZoneConvert/VTimeZone/ChamberOfHorrors.php)
with a hash of $prodId and $vTimeZone.


### Convert DateTimeZone to VTIMEZONE

     (string) TimeZoneConvert::toVTimeZone(mixed $timezone [, DateTime $from = NULL [, DateTime $until]])

Arround the 1990 most timezones of the industrial nations became defined
by a recurring rule and where not tuched since that.  If the VTIMEZONE 
component is requested for a period the timezone could not be described
by a single recurring rule, the library will describe it by its transition
dates.

If the $from parameter is ommited the definition in computed from 
DateTime('now'). If $until is ommited the definition is computed for the
period PHP maintains informations for.

