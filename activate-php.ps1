$env:PATH = "$PSScriptRoot\.tools\php;$env:PATH"
$env:COMPOSER_CAFILE = "$PSScriptRoot\.tools\cacert.pem"
$env:COMPOSER_CACHE_DIR = "$PSScriptRoot\.tools\composer-cache"
$env:COMPOSER_HOME = "$PSScriptRoot\.tools\composer-home"
Write-Host 'PHP lokal aktif untuk terminal ini.'
php -v
