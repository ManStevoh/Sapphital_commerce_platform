# Install tracked git hooks into .git/hooks/
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$hooksSrc = Join-Path $Root "scripts\git-hooks"
$hooksDst = Join-Path $Root ".git\hooks"

if (-not (Test-Path $hooksDst)) {
    Write-Error "Not a git repository: $Root"
}

$psHook = ($hooksSrc -replace '\\', '/') + "/post-commit.ps1"
$rootUnix = ($Root -replace '\\', '/')

# Git for Windows runs hooks via sh; background push must not block commit
$postCommit = @"
#!/bin/sh
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "$psHook" &
exit 0
"@

$hookPath = Join-Path $hooksDst "post-commit"
[System.IO.File]::WriteAllText($hookPath, $postCommit.Replace("`r`n", "`n"))
Write-Host "Installed post-commit hook -> auto-push on every commit"
