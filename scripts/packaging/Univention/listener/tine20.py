# -*- coding: utf-8 -*-
#
# Tine 2.0 for UCS
#  listener module
#
# Copyright 2012-2012 by Metaways Infosystems GmbH
#
# http://www.metaways.de/
#
# All rights reserved.
#
# The source code of this program is made available
# under the terms of the GNU Affero General Public License version 3
# (GNU AGPL V3) as published by the Free Software Foundation.
#
# Binary versions of this program provided by Univention to you as
# well as other copyrighted, protected or trademarked materials like
# Logos, graphics, fonts, specific documentations and configurations,
# cryptographic keys etc. are subject to a license agreement between
# you and Univention and not subject to the GNU AGPL V3.
#
# In the case you use this program under the terms of the GNU AGPL V3,
# the program is provided in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public
# License with the Debian GNU/Linux or Univention distribution in file
# /usr/share/common-licenses/AGPL-3; if not, see
# <http://www.gnu.org/licenses/>.

__package__=''     # workaround for PEP 366
import listener
import subprocess
import univention.debug

name='tine20'
description='update database of Tine 2.0 on changes in UCS LDAP'
filter='(&(objectClass=posixAccount))'
attributes=[]

def handler(dn, new, old):
    if new and not old:
        univention.debug.debug(univention.debug.LISTENER, univention.debug.PROCESS, 'tine20: new user')
    
    if new and old:
        univention.debug.debug(univention.debug.LISTENER, univention.debug.PROCESS, 'tine20: update user')
    
    if not new and old:
        univention.debug.debug(univention.debug.LISTENER, univention.debug.PROCESS, 'tine20: delete user')
