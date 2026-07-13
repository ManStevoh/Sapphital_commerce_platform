# Install tracked git hooks into .git/hooks/
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$hooksSrc = Join-Path $Root "scripts\git-hooks"
$hooksDst = Join-Path $Root ".git\hooks"

if (-not (Test-Path $hooksDst)) {
    Write-Error "Not a git repository: $Root"
}

# post-commit: invoke PowerShell hook script after each commit
$postCommit = @"
@echo off
powershell -NoProfile -ExecutionPolicy Bypass -File "$hooksSrc\post-commit.ps1"
exit /b 0
"@

Set-Content -Path (Join-Path $hooksDst "post-commit") -Value $postCommit -Encoding ASCII
Write-Host "Installed post-commit hook -> auto-push on every commit"
