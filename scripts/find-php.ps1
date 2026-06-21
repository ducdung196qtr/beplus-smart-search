# Find php.exe for Local WP / Laragon / XAMPP (Windows).
# Run from plugin root: powershell -File scripts/find-php.ps1

$paths = @()

if ($env:LOCALAPPDATA) {
	$localRoot = Join-Path $env:LOCALAPPDATA 'Programs\Local\lightning-services'
	if (Test-Path $localRoot) {
		$paths += Get-ChildItem $localRoot -Recurse -Filter 'php.exe' -ErrorAction SilentlyContinue |
			Select-Object -ExpandProperty FullName
	}
}

$extras = @(
	'C:\laragon\bin\php\php.exe',
	'C:\xampp\php\php.exe'
)

foreach ($extra in $extras) {
	if (Test-Path $extra) {
		$paths += $extra
	}
}

try {
	$global = (Get-Command php -ErrorAction Stop).Source
	$paths = @($global) + $paths
} catch {
	# php not on PATH
}

$paths = $paths | Select-Object -Unique

if (-not $paths.Count) {
	Write-Host 'No php.exe found.'
	Write-Host ''
	Write-Host 'Try one of these:'
	Write-Host '  1. Local → Open site shell → npm run composer:install'
	Write-Host '  2. winget install PHP.PHP.8.2'
	Write-Host '  3. Set PHP_BIN manually in .env (see .env.example)'
	exit 1
}

Write-Host 'Found PHP:'
foreach ($p in $paths) {
	Write-Host "  $p"
}

Write-Host ''
Write-Host 'Add to .env (pick one path):'
Write-Host "PHP_BIN=$($paths[0])"
