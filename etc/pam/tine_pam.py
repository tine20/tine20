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

def _authenticate_tine(data, config):
  import requests

  h = { 'user-agent': 'Tine-PAM/0.1', }
  r = requests.post(config.get('url') + config.get('api'), json=data, headers=h, verify=True)
  return r.json()

def _authenticate(pamh, config, syslog):
  data = {
    'username': pamh.get_user(None),
    'password': pamh.authtok,
  }
  if config.get('required-group'):
    data['required-group'] = config.get('required-group')

  response = _authenticate_tine(data, config)
  if response.get('login-success', False):
    r = pamh.PAM_SUCCESS
    syslog.syslog(syslog.LOG_DEBUG, '%s: user %s login success' % (__name__, data['username']))
  else:
    r = pamh.PAM_AUTH_ERR
    error = response.get('error', False)
    if error:
      syslog.syslog(syslog.LOG_ERR, '%s: user %s login error %s' % (__name__, data['username'], error.get('message', 'unknown error')))
    else:
      syslog.syslog(syslog.LOG_DEBUG, '%s: user %s login failed' % (__name__, data['username']))

  return r

def pam_sm_authenticate(pamh, flags, argv):
  import syslog
  import traceback

  config = _parse_args(argv)
  debug = config.get("debug")
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
