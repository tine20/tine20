# -*- coding: utf-8 -*-
#
# Tine 2.0 for UCS
#  config registry module to update Tine 2.0 configuration
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

import sys, re

def handler(bc,changes):
    db_dbname   = bc.get('tine20/cfg/server/mysql_database', 'tine20')
    db_host     = bc.get('tine20/cfg/server/mysql_host'    , 'localhost')
    db_username = bc.get('tine20/cfg/server/mysql_user'    , 'tine20')
    
    db_password_file = bc.get('tine20/cfg/server/mysql_password', '@&@/etc/tine20/mysql.secret@&@').replace('@&@','')
    try:
        db_password = open(db_password_file,'r').readline().strip()
    except IOError, e:
        db_password = None
    
    try:
        f = open('/etc/tine20/config.inc.php', 'r')
    except IOError, e:
        print e
        return
        
    newlines = []
    line = f.readline()
    
    while line:
        if re.search('.*database.*=> array', line):
            
            newlines.append(line)
            line = f.readline()
            
            while not re.search('\A\s*\)', line):
                if re.search('.*host.*=>', line):
                    line = '        \'host\'          => \'%s\',\n' % str(db_host)
                
                if re.search('.*dbname.*=>', line):
                    line = '        \'dbname\'        => \'%s\',\n' % str(db_dbname)
                    
                if re.search('.*username.*=>', line):
                    line = '        \'username\'      => \'%s\',\n' % str(db_username)
        
                if re.search('.*password.*=>', line) and db_password:
                    line = '        \'password\'      => \'%s\',\n' % str(db_password)
        
                newlines.append(line)
                line = f.readline()
                
        newlines.append(line)
        line = f.readline()
            
    f.close()
    
    try:
        f = open('/etc/tine20/config.inc.php', 'w')
    except IOError, e:
        print e
        return
        
    f.truncate();
    f.writelines(newlines)
    f.close()

