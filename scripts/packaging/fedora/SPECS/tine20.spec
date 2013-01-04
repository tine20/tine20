# TODO: strip out OpenLayers and require openlayers
# TODO: add postxxx sections calling the setup.php as command line tool
# FIXME: change selinux context to httpd_log_t for /var/log/tine20/tine20.log
# FIXME: SELinux is preventing /usr/sbin/httpd from create access on the None zend_cache--a.

# This package contains some bundled libraries, here is what has to be done with those:
# Ajam/         - not found in Fedora
# ExtJS/        - not found in Fedora
# GeoExt/       - not found in Fedora
# Hash/         - not found in Fedora
# HTMLPurifier/ - found php-channel-htmlpurifier, but that one doesn't seem to
#                 be enough requirable, will rest with the one delivered with
#                 Tine 2.0
# idnaconvert/  - Fedora has php-IDNA_Convert-0.6.3-4.fc15.noarch
#                 Tine 2.0 has 0.8.0. Either the Fedora package should be
#                 updated, or we stay at the Tine 2.0 version
# jsb2tk/       - not found in Fedora
# OpenDocument/ - not found in Fedora
# OpenLayers/   - found openlayers-2.9.1-4.fc15.noarch, will be stripped out
# PHPExcel/     - not found in Fedora
# qCal/         - not found in Fedora
# Sabre/        - not found in Fedora
# StreamFilter/ - not found in Fedora
# vcardphp/     - not found in Fedora
# Wbxml/        - not found in Fedora
# Zend/         - there is php-ZendFramework, strip it out

%global vyear 2012
%global vmonth 10
%global vmin 3

Name:           tine20
Version:        %{vyear}.%{vmonth}.%{vmin}
Release:        1%{?dist}
Summary:        Open Source Groupware and CRM

License:        AGPLv3, GPLv3, BSD, LGPLv2.1+, LGPLv2.1
URL:            http://www.tine20.org/
Source0:        http://www.tine20.org/downloads/%{version}/%{name}-allinone_%{version}.tar.bz2
Source1:        %{name}-httpd.conf
Source2:        %{name}-php.ini
Source3:        %{name}-config.inc.php
Source4:        %{name}-logrotate.conf
Source5:        %{name}-README.fedora
Source6:        %{name}-cron
Source7:        http://www.tine20.org/downloads/%{version}/%{name}-humanresources_%{version}.tar.bz2

Requires:       %{name}-webstack = %{version}
Requires:       mysql-server

BuildArch:      noarch

%description
Tine 2.0 is an open source project which combines groupware and CRM in one
consistent interface. Tine 2.0 is web-based and optimises collaboration and
organisation of groups in a lasting manner. Tine 2.0 unites all the
advantages of open source software with an extraordinarily high level of
usability and an equally high standard of professional software development.
This is what makes the difference between Tine 2.0 and many other existing
groupware solutions.

Tine 2.0 includes address book, calendar, email, tasks, time tracking and
CRM. Intelligent functions and links make collaboration in Tine 2.0 a true
pleasure and include:

 * Synchronising mobile telephones
 * VoiP integration
 * Flexible assigning of authorisation rights
 * Dynamic lists
 * Search functions
 * History
 * PDF export

%package webstack
Summary:        Tine 2.0 webserver integration package
Requires:       httpd
Requires:       php >= 5.3.0
Requires:       php-gd php-mysqli php-mcrypt php-pecl-apc
Requires:       php-ZendFramework php-ZendFramework-Ldap
Requires:       %{name}-tinebase %{name}-activesync %{name}-calendar %{name}-crm %{name}-felamimail %{name}-filemanager %{name}-projects %{name}-sales %{name}-tasks %{name}-timetracker

%description webstack
This package integrates Tine 2.0 with the webserver, by installing all needed
dependencies to make Tine 2.0 available via HTTP(S).

%package tinebase
Summary:        Tine 2.0 base package
Requires:       %{name}-libraries = %{version}

%description tinebase
This package contains the base which at least is necessary to run Tine 2.0.

%package libraries
Summary:        Additional libraries required by Tine 2.0

%description libraries
Libraries bundled with upstream Tine 2.0 source package, but developed by other developers.

%package activesync
Summary:        Tine 2.0 activesync module
Requires:       %{name}-tinebase = %{version}

%description activesync
This package contains the activesync module for Tine 2.0.

%package calendar
Summary:        Tine 2.0 calendar module
Requires:       %{name}-tinebase = %{version}

%description calendar
This package contains the calendar module for Tine 2.0.


%package crm
Summary:        Tine 2.0 CRM module
Requires:       %{name}-tinebase = %{version}
Requires:       %{name}-sales = %{version}
Requires:       %{name}-tasks = %{version}

%description crm
This package contains the CRM module for Tine 2.0.


%package felamimail
Summary:        Tine 2.0 mail client module
Requires:       %{name}-tinebase = %{version}

%description felamimail
This package contains the mail client module for Tine 2.0 called "Felamimail".


%package filemanager
Summary:        Tine 2.0 file manager module
Requires:       %{name}-tinebase = %{version}

%description filemanager
This package contains the file manager module for Tine 2.0.


%package projects
Summary:        Tine 2.0 project module
Requires:       %{name}-tinebase = %{version}

%description projects
This package contains the projects module for Tine 2.0.

%package sales
Summary:        Tine 2.0 sales module
Requires:       %{name}-tinebase = %{version}

%description sales
This package contains the sales module for Tine 2.0.


%package tasks
Summary:        Tine 2.0 tasks module
Requires:       %{name}-tinebase = %{version}

%description tasks
This package contains the tasks module for Tine 2.0.


%package timetracker
Summary:        Tine 2.0 time tracker module
Requires:       %{name}-tinebase = %{version}

%description timetracker
This package contains the time tracker module for Tine 2.0.


%prep
%setup -q -c -n %{name}-%{version}
%{__cp} -a %{SOURCE5} README.fedora


%build
# nothing to do here so far..

%install
%{__rm} -rf $RPM_BUILD_ROOT


## remove the bundled ZendFramework, the Fedora-shipped one is referenced from
## tine20-httpd.conf which will be installed as /etc/httpd/conf.d/tine20.conf
#%{__rm} -rf library/Zend/


# installation of code to /usr/share/tine20
%{__install} -d $RPM_BUILD_ROOT%{_datadir}/%{name}/
%{__cp} -ad * $RPM_BUILD_ROOT%{_datadir}/%{name}/
%{__rm} -f $RPM_BUILD_ROOT%{_datadir}/%{name}/{[R]*,config.inc.php.dist}

# session and other stuff
%{__install} -d $RPM_BUILD_ROOT%{_sharedstatedir}/%{name}/{tmp,sessions,files,cache}

# httpd configuration
%{__install} -d $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/
%{__install} -pm 644 %{SOURCE1} $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/%{name}.conf

# php.ini needed if FastCGI is used
%{__install} -d $RPM_BUILD_ROOT%{_sysconfdir}/php.d/
%{__install} -pm 644 %{SOURCE2} $RPM_BUILD_ROOT%{_sysconfdir}/php.d/%{name}.ini

# Tine 2.0 configuration
%{__install} -d $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/
%{__install} -pm 640 %{SOURCE3} $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/config.inc.php

# logging
%{__install} -d $RPM_BUILD_ROOT%{_localstatedir}/log/%{name}
%{__install} -d $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
%{__install} -pm 644 %{SOURCE4} $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/%{name}

# cron
%{__install} -d $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/
%{__install} -pm 644 %{SOURCE6} $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/%{name}

%post
if [ "$1" -eq "1" ]; then
    export NEWPASS=$( dd if=/dev/urandom bs=20 count=1 2>/dev/null \
        | sha1sum | awk '{print $1}' )
    sed -i "s/SETUP PASSWORD/$NEWPASS/" %{_sysconfdir}/%{name}/config.inc.php
fi

%files


%files tinebase
%doc LICENSE PRIVACY README RELEASENOTES config.inc.php.dist README.fedora docs/htaccess
%dir %{_datadir}/%{name}/
%{_datadir}/%{name}/Addressbook/
%{_datadir}/%{name}/Admin/
%{_datadir}/%{name}/images/
%{_datadir}/%{name}/index.php
%{_datadir}/%{name}/langHelper.php
%{_datadir}/%{name}/Setup/
%{_datadir}/%{name}/setup.php
%{_datadir}/%{name}/styles/
%{_datadir}/%{name}/%{name}.php
%{_datadir}/%{name}/Tinebase/
%{_datadir}/%{name}/Zend/
%{_datadir}/%{name}/LICENSE
%{_datadir}/%{name}/PRIVACY
%{_datadir}/%{name}/bootstrap.php
%{_datadir}/%{name}/CREDITS
%{_datadir}/%{name}/docs/htaccess

%dir %{_sysconfdir}/%{name}/
%config(noreplace) %attr(0640,root,apache) %{_sysconfdir}/%{name}/config.inc.php
%config(noreplace) %{_sysconfdir}/httpd/conf.d/%{name}.conf
%config(noreplace) %{_sysconfdir}/php.d/tine20.ini
%config            %{_sysconfdir}/cron.d/tine20

%dir %{_sharedstatedir}/%{name}/
%dir %attr(0750,apache,apache) %{_sharedstatedir}/%{name}/tmp
%dir %attr(0750,apache,apache) %{_sharedstatedir}/%{name}/sessions
%dir %attr(0750,apache,apache) %{_sharedstatedir}/%{name}/files
%dir %attr(0750,apache,apache) %{_sharedstatedir}/%{name}/cache

%dir %attr(0750,apache,apache) %{_localstatedir}/log/%{name}/
%config(noreplace) %{_sysconfdir}/logrotate.d/%{name}

%files libraries
%{_datadir}/%{name}/library/

%files activesync
%{_datadir}/%{name}/ActiveSync/


%files calendar
%{_datadir}/%{name}/Calendar/


%files crm
%{_datadir}/%{name}/Crm/


%files felamimail
%{_datadir}/%{name}/Felamimail/


%files filemanager
%{_datadir}/%{name}/Filemanager/


%files projects
%{_datadir}/%{name}/Projects/


%files sales
%{_datadir}/%{name}/Sales/


%files tasks
%{_datadir}/%{name}/Tasks/


%files timetracker
%{_datadir}/%{name}/Timetracker/


%files webstack

%changelog
* Wed Jan 04 2013 Lars Kneschke <l.kneschke@metaways.de> - 2012.10.3-1
- New upstream release Joey SR 3 (2012.10.3)

* Wed Jan 02 2013 Lars Kneschke <l.kneschke@metaways.de> - 2012.10.2-1
- new upstream release 2012.10.2

* Mon Nov 05 2012 Dominic Hopf <dmaphy@fedoraproject.org> - 2012.10.1-1
- new upstream release 2012.10.1

* Thu Aug 02 2012 Dominic Hopf <dmaphy@fedoraproject.org> - 2012.03.5-2
- re-enable the Tine 2.0 delivered Zend Framework
  
* Fri Jun 29 2012 Dominic Hopf <dmaphy@fedoraproject.org> - 2012.03.5-1
- Update to new upstream release 2012.03.05

* Tue Mar 13 2012 Dominic Hopf <dmaphy@fedoraproject.org> - 2012.03.1-1
- Update to new upstream release 2012.03.01

* Sat Feb 04 2012 Dominic Hopf <dmaphy@fedoraproject.org> - 2011.05.6-1
- Update to new service release 2011.05.06
- comment out the Filemanager subpackage, the module disappeared in 2011-05-06
- fix the sed-command for setting the setup password

* Tue Dec 27 2011 Dominic Hopf <dmaphy@fedoraproject.org> - 2011.05.5-1
- Update to new service release 2011.05.05
- link to php-ZendFramework shipped with Fedora
- don't remove files LICENSE and PRIVACY, they are reference from setup.php

* Sun Nov 13 2011 Dominic Hopf <dmaphy@fedoraproject.org> - 2011.05.4-1
- initial Fedora package of Tine 2.0
