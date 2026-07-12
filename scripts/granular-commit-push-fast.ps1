# Fast granular commit + push — one commit per directory (all files in that folder)
# Usage: .\scripts\granular-commit-push-fast.ps1 [-PushEvery 50]
param(
    [int]$PushEvery = 50
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

$ExcludePattern = '\\vendor\\|\\node_modules\\|\\.env$|\\.env\.|\\.git\\|\\.docusaurus\\|\\docs-site\\build\\|\\uploads\\|\\.phpunit\.|Thumbs\.db|\.DS_Store'

function Get-CommitPrefix([string]$RelativePath) {
    if ($RelativePath -match '\.(md|mdx)$') { return 'docs' }
    if ($RelativePath -match '(test|Test|spec)\.') { return 'test' }
    if ($RelativePath -match '\.(tsx?|jsx?|php)$') { return 'feat' }
    return 'chore'
}

if (-not (Test-Path ".git")) {
    git init
    git branch -M main
}
if (-not (git remote get-url origin 2>$null)) {
    git remote add origin "https://github.com/ManStevoh/Sapphital_commerce_platform.git"
}

$allFiles = Get-ChildItem -Recurse -File | Where-Object {
    $_.FullName.Substring($Root.Length + 1) -notmatch $ExcludePattern
}

# Group by parent directory
$groups = $allFiles | Group-Object { Split-Path $_.FullName -Parent } | Sort-Object Name

$count = 0
$total = $groups.Count
Write-Host "Committing $total directories..."

foreach ($group in $groups) {
    $dirPath = if ($group.Name.Length -gt $Root.Length) {
        $group.Name.Substring($Root.Length + 1)
    } else {
        "."
    }
    if ([string]::IsNullOrWhiteSpace($dirPath)) { $dirPath = "." }

    $prefix = Get-CommitPrefix $dirPath
    $normalized = ($dirPath -replace '\\', '/')
    if ($normalized -eq '.') { $normalized = 'root' }
    $msg = "${prefix}: add ${normalized}"

    $paths = $group.Group | ForEach-Object {
        $_.FullName.Substring($Root.Length + 1)
    }
    foreach ($p in $paths) {
        git add -- "$p" 2>$null
    }
    git diff --cached --quiet 2>$null
    if ($LASTEXITCODE -eq 0) { continue }

    git commit -q -m $msg
    if ($LASTEXITCODE -ne 0) { continue }

    $count++
    if ($count % $PushEvery -eq 0) {
        Write-Host "Pushing after $count commits..."
        Start-Job -ScriptBlock {
            Set-Location $using:Root
            git push origin main 2>&1 | Out-Null
        } | Out-Null
    }
    if ($count % 200 -eq 0) { Write-Host "Progress: $count / $total" }
}

Get-Job | Wait-Job | Out-Null
Write-Host "Final push..."
git push -u origin main 2>&1
Write-Host "Done. $count new commits."
