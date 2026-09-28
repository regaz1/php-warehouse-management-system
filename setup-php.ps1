$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$runtimeDirectory = Join-Path $projectRoot 'runtime\php'
$phpExecutable = Join-Path $runtimeDirectory 'php.exe'

if (Test-Path -LiteralPath $phpExecutable) {
    exit 0
}

$archiveName = 'php-8.5.11-nts-Win32-vs17-x64.zip'
$downloadUrls = @(
    "https://downloads.php.net/~windows/releases/$archiveName",
    "https://downloads.php.net/~windows/releases/archives/$archiveName"
)
$temporaryArchive = Join-Path ([IO.Path]::GetTempPath()) ("warehouse-php-" + [guid]::NewGuid().ToString('N') + '.zip')

try {
    New-Item -ItemType Directory -Path $runtimeDirectory -Force | Out-Null
    $downloaded = $false

    foreach ($url in $downloadUrls) {
        try {
            Write-Host "Downloading PHP 8.5.11 from the official PHP website..."
            Invoke-WebRequest -Uri $url -OutFile $temporaryArchive -UseBasicParsing
            $downloaded = $true
            break
        } catch {
            Write-Host "The download location was not available. Trying the archive..."
        }
    }

    if (-not $downloaded) {
        throw 'PHP could not be downloaded. Check your internet connection or install XAMPP.'
    }

    Expand-Archive -LiteralPath $temporaryArchive -DestinationPath $runtimeDirectory -Force

    $developmentIni = Join-Path $runtimeDirectory 'php.ini-development'
    $phpIni = Join-Path $runtimeDirectory 'php.ini'
    if (-not (Test-Path -LiteralPath $developmentIni)) {
        throw 'The downloaded PHP archive is incomplete.'
    }

    $configuration = [IO.File]::ReadAllText($developmentIni)
    $configuration = $configuration -replace '(?m)^;?extension_dir\s*=\s*"ext"\s*$', 'extension_dir = "ext"'
    $configuration = $configuration -replace '(?m)^;extension=pdo_sqlite\s*$', 'extension=pdo_sqlite'
    $configuration = $configuration -replace '(?m)^;extension=sqlite3\s*$', 'extension=sqlite3'
    [IO.File]::WriteAllText($phpIni, $configuration, (New-Object Text.UTF8Encoding($false)))

    if (-not (Test-Path -LiteralPath $phpExecutable)) {
        throw 'php.exe was not found after extraction.'
    }

    & $phpExecutable -r "exit(extension_loaded('pdo_sqlite') ? 0 : 1);"
    if ($LASTEXITCODE -ne 0) {
        throw 'PDO SQLite could not be enabled.'
    }

    Write-Host 'PHP is ready.'
} finally {
    if (Test-Path -LiteralPath $temporaryArchive) {
        Remove-Item -LiteralPath $temporaryArchive -Force -ErrorAction SilentlyContinue
    }
}
