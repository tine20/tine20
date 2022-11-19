# -*- coding: utf-8 -*-
#
# tine-groupware
#
# @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
# @author       Reinhard Vicinus <r.vicinus@metaways.de>
# @copyright    Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
# @version      0.1

__doc__ = """python-pam binding for tine-groupware

Installation:
=============
place this file in:
    /usr/local/tine_pam.py

add a new pam config file:
    /etc/pam.d/common-tine:

with this contents:
    auth       sufficient   pam_python.so /usr/local/tine_pam.py url=https://my.tine.url
    account    sufficient   pam_permit.so

Parameters:
===========

Parameters to the module are:

 :param url: tine-groupware base url
 :parm api: optional, defaults to /authPAM/validate
 :param required-group: optional, group name the user is required to be member of for auth to succeed

"""

def _parse_args(argv):
  config = { 'api': '/authPAM/validate' }
  for arg in argv:
    if '=' in arg:
      key, value = arg.split('=')
      config[key]=value
    else:
      config[arg]=True
#    key, value, *_ = arg.split('=') + [True]
#    config[key] = value
  return config

def _authenticate_tine(data, config, syslog):
  import requests

  h = { 'user-agent': 'Tine-PAM/0.1', }
  r = requests.post(config.get('url') + config.get('api'), json=data, headers=h, verify=True)
  return r.json()

def _authenticate(pamh, config, syslog):
  if pamh.authtok is None:
    message = pamh.Message(pamh.PAM_PROMPT_ECHO_OFF, "otp: ")
    response = pamh.conversation(message)
    pamh.authtok = response.resp

  data = {
    'user': pamh.get_user(None),
    'pass': pamh.authtok,
  }
  if config.get('required-group'):
    data['required-group'] = config.get('required-group')

  response = _authenticate_tine(data, config, syslog)
  if response.get('login-success', False):
    r = pamh.PAM_SUCCESS
    syslog.syslog(syslog.LOG_DEBUG, '%s: user %s login success' % (__name__, data['user']))
  else:
    r = pamh.PAM_AUTH_ERR
    error = response.get('error', False)
    if error:
      syslog.syslog(syslog.LOG_ERR, '%s: user %s login error %s' % (__name__, data['user'], error.get('message', 'unknown error')))
    else:
      syslog.syslog(syslog.LOG_DEBUG, '%s: user %s login failed' % (__name__, data['user']))

  return r

def pam_sm_authenticate(pamh, flags, argv):
  import syslog
  import traceback

  config = _parse_args(argv)
  syslog.openlog(facility=syslog.LOG_AUTH)

  try:
    r = _authenticate(pamh, config, syslog)
  except Exception as e:
    syslog.syslog(syslog.LOG_ERR, traceback.format_exc())
    syslog.syslog(syslog.LOG_ERR, "%s: %s" % (__name__, e))
    r = pamh.PAM_AUTH_ERR

  syslog.closelog()
  return r

def pam_sm_setcred(pamh, flags, argv):
  return pamh.PAM_SUCCESS

def pam_sm_acct_mgmt(pamh, flags, argv):
  return pamh.PAM_SUCCESS

def pam_sm_open_session(pamh, flags, argv):
  return pamh.PAM_SUCCESS

def pam_sm_close_session(pamh, flags, argv):
  return pamh.PAM_SUCCESS

def pam_sm_chauthtok(pamh, flags, argv):
  return pamh.PAM_SUCCESS
