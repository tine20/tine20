composer helper scripts
--------------------

Find/add composer helper scripts here.


satis helper scripts (composerLockRewrite / satisDirectoryGlitch)
--------------------

Use these scripts to composer install from satis "proxy"

first use composerLockRewrite.php to fix composer.json and composer.lock

for example:

php ./composerLockRewrite.php ../../../tine20/composer.lock

if you want to access another satis host, you can include the hostname (or IP) as param:

php ./composerLockRewrite.php ../../../tine20/composer.lock localhost

then use satisDirectoryGlitch.php to move the zend libraries to the right place:

for example:

php ./satisDirectoryGlitch.php ../../../tin20/vendor/zendframework/