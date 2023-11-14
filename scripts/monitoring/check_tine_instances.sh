#!/bin/bash
#######################################################
# (c) copyright Metaways Infosystems GmbH 2023
# authors:  Philipp Schüle <p.schuele@metaways.de>
#           and other @metaways.de
#######################################################
#
# TODO: make paths configurable
#

VALID_METHODS="monitoringLoginNumber monitoringCheckDB monitoringCheckCron monitoringCheckQueue monitoringCheckCache monitoringCheckConfig monitoringCheckLicense monitoringMailServers"
METHOD_FOUND=0
WARNINGS=0
CRITICALS=0
WARNING_INSTANCES=""
CRITICAL_INSTANCES=""

usage() {
cat <<EOU
$0 [-v] -m <method> -b <blacklist>
        -v verbose output (bash debugging)
EOU
}

while getopts 'm:b:vh' OPTION
do
  case "$OPTION" in
    m)
      METHOD=$OPTARG
    ;;
    b)
      BLACKLIST=$OPTARG
    ;;
    v)
      set -x
    ;;
    h)
      usage
      exit 2
    ;;
    "?")
      echo "Unknown option $OPTARG"
      exit 1;
    ;;
    ":")
      echo "No argument value for option $OPTARG"
      exit 1;
    ;;
  esac
done


for i in $VALID_METHODS; do
	if [ "$METHOD" == "$i" ]; then
		METHOD_FOUND=1
		break;
	fi
done

if [ $METHOD_FOUND -eq 0 ]; then
    echo "Please provide a valid method via -m option!"
    exit 3;
fi

BLACKLIST="$BLACKLIST removed system"

if [ -f /var/lib/nagios/check_tine_blacklist ]; then
  . /var/lib/nagios/check_tine_blacklist
fi

for INSTANCE in `ls -1 /srv/tine20/customers`; do
    for i in $BLACKLIST; do
	    if [ $INSTANCE == $i ]; then
		    continue 2;
	    fi
    done
    CMD="php /srv/tine20/customers/$INSTANCE/www/rodata/htdocs/tine20.php --config /srv/tine20/customers/$INSTANCE/system/phpincludes/config.inc.php --method=Tinebase.$METHOD"
    if [ ! -f /srv/tine20/customers/$INSTANCE/DONOTCHECK ]
    then
      res=$($CMD)
    else
      res=0
    fi

    case $? in
      1)  WARNINGS=$(($WARNINGS+1))
          WARNING_INSTANCES="$WARNING_INSTANCES $INSTANCE";;
      2)  CRITICALS=$(($CRITICALS+1))
          CRITICAL_INSTANCES="$CRITICAL_INSTANCES $INSTANCE";;
      0)  ;;
      *)  CRITICALS=$(($CRITICALS+1))
          CRITICAL_INSTANCES="$CRITICAL_INSTANCES $INSTANCE";;
    esac
done


if [ $CRITICALS -gt 0 ]; then
	echo "There were $CRITICALS criticals for $CRITICAL_INSTANCES ($METHOD)";
	exit 2;
fi

if [ $WARNINGS -gt 0 ]; then
	echo "There were $WARNINGS warnings for $WARNING_INSTANCES ($METHOD)";
	exit 1;
fi

echo "OK - Every tine instance seems to be fine ($METHOD)"
exit 0
