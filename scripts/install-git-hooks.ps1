# Install tracked git hooks into .git/hooks/
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

$hooksDst = Join-Path $Root ".git\hooks"
if (-not (Test-Path $hooksDst)) {
    Write-Error "Not a git repository: $Root"
}

# Pure sh hook — Git for Windows runs this via bash; push in background
$postCommit = @"
#!/bin/sh
branch=`$(git rev-parse --abbrev-ref HEAD 2>/dev/null)
[ -z "`$branch" ] || [ "`$branch" = "HEAD" ] && exit 0
echo "[`$(date '+%Y-%m-%d %H:%M:%S')] pushing `$branch" >> .git/auto-push.log
git push origin "`$branch" >> .git/auto-push.log 2>&1 &
exit 0
"@

$hookPath = Join-Path $hooksDst "post-commit"
[System.IO.File]::WriteAllText($hookPath, $postCommit.Replace("`r`n", "`n"))
Write-Host "Installed post-commit hook -> auto-push on every commit"
