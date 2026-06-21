# Push to origin after prepush checks.
# Usage: npm run git:push

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

$gitCandidates = @(
	'git',
	'C:\Program Files\Git\cmd\git.exe',
	'C:\Program Files\Git\bin\git.exe'
)

$git = $null
foreach ($candidate in $gitCandidates) {
	if ($candidate -eq 'git') {
		try {
			$null = Get-Command git -ErrorAction Stop
			$git = 'git'
			break
		} catch {
			continue
		}
	} elseif (Test-Path $candidate) {
		$git = $candidate
		break
	}
}

if (-not $git) {
	Write-Host 'Git not found. Run: npm run setup:git-path'
	exit 1
}

Write-Host 'Running prepush checks (Composer + CI) …'
npm run prepush
if ($LASTEXITCODE -ne 0) {
	exit $LASTEXITCODE
}

$branch = & $git rev-parse --abbrev-ref HEAD
Write-Host "Pushing $branch to origin …"
& $git push -u origin $branch
exit $LASTEXITCODE
