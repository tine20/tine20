from __future__ import with_statement
import unittest

from sikuli.Sikuli import *

class Chromium(object):

    def __init__(self, url):
        self.url = url
 
    def __enter__(self):
        Screen(0)
        app = App.open('chromium-browser')
        wait(2)
        type("%s\n" % self.url)    
        wait(2)
        
    def __exit__(self, type_, value, traceback):
        type(Key.F4, KEY_ALT)

        
class TestBasicScenario(unittest.TestCase):
 
    def test_01_login(self):
        click("username.png")
        type("unittest")
        click("password.png")
        type("password")
        click("1379932483974.png")
        wait(3)
        assert exists("1389349522098.png")
        

    def test_02_logout(self):
        click("logout.png")
        click("1389349732360.png")
        wait(3)       
        assert exists("loginlogo.png")
        


# Sikuli settings (no logs)
Settings.ActionLogs = False
Settings.InfoLogs = False
Settings.DebugLogs = False
 
with Chromium('localhost&tine20'):
    suite = unittest.TestLoader().loadTestsFromTestCase(TestBasicScenario)
    unittest.TextTestRunner(verbosity=2).run(suite)

