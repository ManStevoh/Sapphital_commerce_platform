# Auto-push to origin after every local commit (runs in background).
$ErrorActionPreference = "SilentlyContinue"
$repoRoot = git rev-parse --show-toplevel 2>$null
if (-not $repoRoot) { exit 0 }

Set-Location $repoRoot
$branch = git rev-parse --abbrev-ref HEAD 2>$null
if (-not $branch -or $branch -eq "HEAD") { exit 0 }

$logFile = Join-Path $repoRoot ".git\auto-push.log"
$ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"

Start-Process -WindowStyle Hidden -FilePath "git" `
    -ArgumentList @("push", "origin", $branch) `
    -WorkingDirectory $repoRoot `
    -RedirectStandardOutput $logFile `
    -RedirectStandardError $logFile `
    | Out-Null

Add-Content -Path $logFile -Value "[$ts] queued push for $branch"

exit 0
