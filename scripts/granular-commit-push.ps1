# Granular commit + push bootstrap for Sapphital Commerce Platform
# Usage: .\scripts\granular-commit-push.ps1 [-PushEvery 25] [-DryRun]
param(
    [int]$PushEvery = 25,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

$ExcludePattern = '\\vendor\\|\\node_modules\\|\\.env$|\\.env\.|\\.git\\|\\.docusaurus\\|\\docs-site\\build\\|\\uploads\\|\\.phpunit\.|Thumbs\.db|\.DS_Store'

function Get-CommitPrefix([string]$RelativePath) {
    if ($RelativePath -match '\.(md|mdx)$') { return 'docs' }
    if ($RelativePath -match '(test|Test|spec)\.') { return 'test' }
    if ($RelativePath -match '\.(tsx?|jsx?)$') { return 'feat' }
    if ($RelativePath -match '\.(php)$') { return 'feat' }
    if ($RelativePath -match '\.(yml|yaml)$') { return 'chore' }
    if ($RelativePath -match '\.(json)$') { return 'chore' }
    return 'chore'
}

function New-CommitMessage([string]$RelativePath) {
    $prefix = Get-CommitPrefix $RelativePath
    $normalized = $RelativePath -replace '\\', '/'
    return "${prefix}: add ${normalized}"
}

if (-not (Test-Path ".git")) {
    git init
    git branch -M main
}

if (-not (git remote get-url origin 2>$null)) {
    git remote add origin "https://github.com/ManStevoh/Sapphital_commerce_platform.git"
}

# Track .gitignore first
if (-not $DryRun) {
    git add .gitignore 2>$null
    git diff --cached --quiet 2>$null
    if ($LASTEXITCODE -ne 0) {
        git commit -m "chore: add root gitignore"
    }
}

$files = Get-ChildItem -Recurse -File | Where-Object {
    $rel = $_.FullName.Substring($Root.Length + 1)
    $rel -notmatch $ExcludePattern
} | Sort-Object FullName

$count = 0
$total = $files.Count
Write-Host "Processing $total files..."

foreach ($file in $files) {
    $rel = $file.FullName.Substring($Root.Length + 1)
    $msg = New-CommitMessage $rel

    if ($DryRun) {
        Write-Host "[dry-run] $msg"
        continue
    }

    git add -- "$rel" 2>$null
    git diff --cached --quiet 2>$null
    if ($LASTEXITCODE -eq 0) { continue }

    git commit -m $msg
    if ($LASTEXITCODE -ne 0) { continue }

    $count++
    if ($count % $PushEvery -eq 0) {
        Write-Host "Pushing after $count commits..."
        git push -u origin main 2>&1
    }

    if ($count % 100 -eq 0) {
        Write-Host "Progress: $count / $total commits"
    }
}

if (-not $DryRun -and $count -gt 0) {
    Write-Host "Final push ($count total commits)..."
    git push -u origin main 2>&1
}

Write-Host "Done. Created $count commits."
