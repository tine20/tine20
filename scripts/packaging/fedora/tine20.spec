# TODO: strip out OpenLayers and require openlayers
# TODO: add postxxx sections calling the setup.php as command line tool

# This package contains some bundled libraries, here is what is done with those:
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
# Zend/         - there is php-ZendFramework-1.11.11-1.fc16.noarch, will be
#                 stripped out

%global vyear 2011
%global vmonth 05
%global vmin 6
%global upstreamversion %{vyear}-%{vmonth}-%{vmin}
%global downstreamversion %{vyear}.%{vmonth}.%{vmin}

Name:           tine20
Version:        %{downstreamversion}
Release:        1%{?dist}
Summary:        Open Source Groupware and CRM

License:        AGPLv3, GPLv3, BSD, LGPLv2.1+, LGPLv2.1
URL:            http://www.tine20.org/
Source0:        http://www.tine20.org/downloads/%{upstreamversion}/tine20-allinone_%{upstreamversion}.tar.bz2
Source1:        tine20-httpd.conf
Source2:        tine20-php.ini
Source3:        tine20-config.inc.php
Source4:        tine20-logrotate.conf
Source5:        tine20-README.fedora

# The patch is to make some requirements compatible with packages (not) provided
# in Fedora and thus, not implemented upstream 
Patch0:         0001-tine20-fix-requirements.patch

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


%package base
Summary:        Tine 2.0 base package
Requires:       httpd
Requires:       php >= 5.2.3
Requires:       php-gd, php-mysqli, php-mcrypt
Requires:       php-ZendFramework php-ZendFramework-Ldap

%description base
This package contains the base which at least is necessary to run Tine 2.0.


%package calendar
Summary:        Tine 2.0 calendar module
Requires:       tine20-base = %{version}

%description calendar
This package contains the calendar module for Tine 2.0.


%package crm
Summary:        Tine 2.0 CRM module
Requires:       tine20-base = %{version}
Requires:       tine20-sales = %{version}
Requires:       tine20-tasks = %{version}

%description crm
This package contains the CRM module for Tine 2.0.


%package felamimail
Summary:        Tine 2.0 mail client module
Requires:       tine20-base = %{version}

%description felamimail
This package contains the mail client module for Tine 2.0 called "Felamimail".


#%package filemanager
#Summary:        Tine 2.0 file manager module
#Requires:       tine20-base = %{version}
#
#%description filemanager
#This package contains the file manager module for Tine 2.0.


%package sales
Summary:        Tine 2.0 sales module
Requires:       tine20-base = %{version}

%description sales
This package contains the sales module for Tine 2.0.


%package tasks
Summary:        Tine 2.0 tasks module
Requires:       tine20-base = %{version}

%description tasks
This package contains the tasks module for Tine 2.0.


%package timetracker
Summary:        Tine 2.0 time tracker module
Requires:       tine20-base = %{version}

%description timetracker
This package contains the time tracker module for Tine 2.0.


%prep
%setup -q -c -n %{name}-%{downstreamversion}
cp -a %{SOURCE5} README.fedora

%patch0


%build
# nothing to do here so far..

%install
rm -rf $RPM_BUILD_ROOT


# remove the bundled ZendFramework, the Fedora-shipped one is referenced from
# tine20-httpd.conf which will be installed as /etc/httpd/conf.d/tine20.conf
rm -rf library/Zend/


# installation of code to /usr/share/tine20
install -d $RPM_BUILD_ROOT%{_datadir}/%{name}/
cp -ad * $RPM_BUILD_ROOT%{_datadir}/%{name}/
rm -f $RPM_BUILD_ROOT%{_datadir}/%{name}/{[R]*,config.inc.php.dist}

# session and other stuff
install -d $RPM_BUILD_ROOT%{_sharedstatedir}/%{name}/{tmp,sessions,files,cache}

# httpd configuration
install -d $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/
install -pm 644 %{SOURCE1} $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/%{name}.conf

# php.ini needed if FastCGI is used
install -d $RPM_BUILD_ROOT%{_sysconfdir}/php.d/
install -pm 644 %{SOURCE2} $RPM_BUILD_ROOT%{_sysconfdir}/php.d/%{name}.ini

# Tine 2.0 configuration
install -d $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/
install -pm 640 %{SOURCE3} $RPM_BUILD_ROOT%{_sysconfdir}/%{name}/config.inc.php

# logging
install -d $RPM_BUILD_ROOT%{_localstatedir}/log/%{name}
install -d $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
install -pm 644 %{SOURCE4} $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/%{name}


%post
if [ "$1" -eq "1" ]; then
        export NEWPASS=$( dd if=/dev/urandom bs=20 count=1 2>/dev/null \
                                | sha1sum | awk '{print $1}' )
	sed -i "s/SETUP PASSWORD/$NEWPASS/" %{_sysconfdir}/%{name}/config.inc.php
fi


%files base
%doc LICENSE PRIVACY README RELEASENOTES config.inc.php.dist README.fedora
%dir %{_datadir}/%{name}/
%{_datadir}/%{name}/Addressbook/
%{_datadir}/%{name}/Admin/
%{_datadir}/%{name}/images/
%{_datadir}/%{name}/index.php
%{_datadir}/%{name}/langHelper.php
%{_datadir}/%{name}/library/
%{_datadir}/%{name}/Setup/
%{_datadir}/%{name}/setup.php
%{_datadir}/%{name}/styles/
%{_datadir}/%{name}/%{name}.php
%{_datadir}/%{name}/Tinebase/
%{_datadir}/%{name}/Zend/
%{_datadir}/%{name}/LICENSE
%{_datadir}/%{name}/PRIVACY

%dir %{_sysconfdir}/%{name}/
%config(noreplace) %attr(0640,root,apache) %{_sysconfdir}/%{name}/config.inc.php
%config(noreplace) %{_sysconfdir}/httpd/conf.d/%{name}.conf
%config(noreplace) %{_sysconfdir}/php.d/tine20.ini

%dir %{_sharedstatedir}/%{name}/
%dir %attr(0750,apache,apache) %{_sharedstatedir}/%{name}/tmp
%dir %attr(0750,apache,apache) %{_sharedstatedir}/%{name}/sessions
%dir %attr(0750,apache,apache) %{_sharedstatedir}/%{name}/files
%dir %attr(0750,apache,apache) %{_sharedstatedir}/%{name}/cache

%dir %attr(0750,apache,apache) %{_localstatedir}/log/%{name}/
%config(noreplace) %{_sysconfdir}/logrotate.d/%{name}


%files calendar
%{_datadir}/%{name}/Calendar/


%files crm
%{_datadir}/%{name}/Crm/


%files felamimail
%{_datadir}/%{name}/Felamimail/


#%files filemanager
#%{_datadir}/%{name}/Filemanager/


%files sales
%{_datadir}/%{name}/Sales/


%files tasks
%{_datadir}/%{name}/Tasks/


%files timetracker
%{_datadir}/%{name}/Timetracker/


%changelog
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
