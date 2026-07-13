# Legacy wrapper — installer now uses inline sh hook. Kept for reference/manual use.
$ErrorActionPreference = "SilentlyContinue"
$repoRoot = git rev-parse --show-toplevel 2>$null
if (-not $repoRoot) { exit 0 }
Set-Location $repoRoot
$branch = git rev-parse --abbrev-ref HEAD 2>$null
if (-not $branch -or $branch -eq "HEAD") { exit 0 }
$logFile = Join-Path $repoRoot ".git\auto-push.log"
"$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') pushing $branch" | Add-Content $logFile
git push origin $branch 2>&1 | Add-Content $logFile
exit 0
