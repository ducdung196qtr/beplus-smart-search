# Push plugin to GitHub (Windows helper).
# Usage: npm run git:push
# Repo: https://github.com/ducdung196qtr/beplus-smart-search

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
	Write-Host 'Git not found. Install Git for Windows: https://git-scm.com/download/win'
	exit 1
}

$remote = 'https://github.com/ducdung196qtr/beplus-smart-search.git'

if (-not (Test-Path '.git')) {
	& $git init
	& $git branch -M main
}

$remotes = & $git remote 2>$null
if ($remotes -notcontains 'origin') {
	& $git remote add origin $remote
} else {
	& $git remote set-url origin $remote
}

& $git add .
$status = & $git status --porcelain
if ($status) {
	& $git commit -m "Initial commit: BePlus Smart Search plugin"
} else {
	Write-Host 'Nothing to commit.'
}

& $git push -u origin main
