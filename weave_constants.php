<?php

# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the "License"); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# The Original Code is Weave Basic Object Server
#
# The Initial Developer of the Original Code is
# Mozilla Labs.
# Portions created by the Initial Developer are Copyright (C) 2008
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#	Toby Elliott (telliott@mozilla.com)
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK *****

#secret using the admin interface, if you are doing so
if (!defined('WEAVE_USER_ADMIN_SECRET')) { define('WEAVE_USER_ADMIN_SECRET', 'bad secret'); }



#engine for auth
#Acceptable values: mysql | sqlite | ldap | none
if (!defined('WEAVE_AUTH_ENGINE')) { define('WEAVE_AUTH_ENGINE', 'mysql'); }

if (WEAVE_AUTH_ENGINE == 'mysql')
{
	#host, db name, username and password for the mysql auth store
	if (!defined('WEAVE_MYSQL_AUTH_HOST')) { define('WEAVE_MYSQL_AUTH_HOST', 'localhost'); }
	if (!defined('WEAVE_MYSQL_AUTH_DB')) { define('WEAVE_MYSQL_AUTH_DB', 'weave'); }
	if (!defined('WEAVE_MYSQL_AUTH_USER')) { define('WEAVE_MYSQL_AUTH_USER', 'weave'); }
	if (!defined('WEAVE_MYSQL_AUTH_PASS')) { define('WEAVE_MYSQL_AUTH_PASS', 'weave'); }
}
elseif (WEAVE_AUTH_ENGINE == 'sqlite')
{
	#path to the sqlite db
	if (!defined('WEAVE_SQLITE_AUTH_DIRECTORY')) { define('WEAVE_SQLITE_AUTH_DIRECTORY', '/Library/WebServer/dbs'); }
}
elseif (WEAVE_AUTH_ENGINE == 'ldap')
{
	if (!defined('WEAVE_LDAP_AUTH_HOST')) { define('WEAVE_LDAP_AUTH_HOST', 'localhost'); }
	if (!defined('WEAVE_LDAP_AUTH_USER')) { define('WEAVE_LDAP_AUTH_USER', 'weave'); }
	if (!defined('WEAVE_LDAP_AUTH_PASS')) { define('WEAVE_LDAP_AUTH_PASS', 'weave'); }
	if (!defined('WEAVE_LDAP_AUTH_DN')) { define('WEAVE_LDAP_AUTH_DN', ''); }
	if (!defined('WEAVE_LDAP_AUTH_USER_PARAM_NAME')) { define('WEAVE_LDAP_AUTH_USER_PARAM_NAME', 'uid'); }
	if (!defined('WEAVE_LDAP_CLUSTER')) { define('WEAVE_LDAP_CLUSTER', ''); }
}


#engine for storage.
#Acceptable values: mysql | sqlite
if (!defined('WEAVE_STORAGE_ENGINE')) { define('WEAVE_STORAGE_ENGINE', 'mysql'); }

if (WEAVE_STORAGE_ENGINE == 'mysql')
{
	#if using mysql, host, db name, username and password for the auth store reader
	if (!defined('WEAVE_MYSQL_STORE_READ_HOST')) { define('WEAVE_MYSQL_STORE_READ_HOST', 'localhost'); }
	if (!defined('WEAVE_MYSQL_STORE_READ_DB')) { define('WEAVE_MYSQL_STORE_READ_DB', 'weave'); }
	if (!defined('WEAVE_MYSQL_STORE_READ_USER')) { define('WEAVE_MYSQL_STORE_READ_USER', 'weave'); }
	if (!defined('WEAVE_MYSQL_STORE_READ_PASS')) { define('WEAVE_MYSQL_STORE_READ_PASS', 'weave'); }

	#if using mysql, host, db name, username and password for the auth store writer
	if (!defined('WEAVE_MYSQL_STORE_WRITE_HOST')) { define('WEAVE_MYSQL_STORE_WRITE_HOST', WEAVE_MYSQL_STORE_READ_HOST); }
	if (!defined('WEAVE_MYSQL_STORE_WRITE_DB')) { define('WEAVE_MYSQL_STORE_WRITE_DB', WEAVE_MYSQL_STORE_READ_DB); }
	if (!defined('WEAVE_MYSQL_STORE_WRITE_USER')) { define('WEAVE_MYSQL_STORE_WRITE_USER', WEAVE_MYSQL_STORE_READ_USER); }
	if (!defined('WEAVE_MYSQL_STORE_WRITE_PASS')) { define('WEAVE_MYSQL_STORE_WRITE_PASS', WEAVE_MYSQL_STORE_READ_PASS); }

}
elseif (WEAVE_STORAGE_ENGINE == 'sqlite')
{
	#if using sqlite, path to the sqlite db
	if (!defined('WEAVE_SQLITE_STORE_DIRECTORY')) { define('WEAVE_SQLITE_STORE_DIRECTORY', '/Library/WebServer/dbs'); }
}

#if you are using mysql for both auth and storage and they live in the same table, you may
#share the database handle
if (!defined('WEAVE_SHARE_DBH')) { define('WEAVE_SHARE_DBH', 0); }

#The maximum size of a payload (set to 0 for unlimited) in bytes. Watch out for large characters!
if (!defined('WEAVE_PAYLOAD_MAX_SIZE')) { define('WEAVE_PAYLOAD_MAX_SIZE', 262144); } #256K

#The maximum quota per user in K (not really used yet)
if (!defined('WEAVE_QUOTA')) { define('WEAVE_QUOTA', 5000); } #5M

#A default username and password, useful for testing suites and the heartbeat
if (!defined('WEAVE_DEFAULT_TEST_USERNAME')) { define('WEAVE_DEFAULT_TEST_USERNAME', 'testuser'); }
if (!defined('WEAVE_DEFAULT_TEST_PASSWORD')) { define('WEAVE_DEFAULT_TEST_PASSWORD', 'testpass'); }




?>