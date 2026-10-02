@echo off
setlocal
set "PATH=%~dp0.tools\php;%PATH%"
set "COMPOSER_CAFILE=%~dp0.tools\cacert.pem"
set "COMPOSER_CACHE_DIR=%~dp0.tools\composer-cache"
set "COMPOSER_HOME=%~dp0.tools\composer-home"
"%~dp0.tools\php\php.exe" "%~dp0.tools\composer.phar" %*
exit /b %errorlevel%
