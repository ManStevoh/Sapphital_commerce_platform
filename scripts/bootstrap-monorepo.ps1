# Bootstrap monorepo: flatten nested git repos, commit by component, push in batches
param([int]$PushEvery = 30)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

function Invoke-Git {
    param([string[]]$Args)
    $prev = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    & git @Args 2>&1 | Out-Null
    $code = $LASTEXITCODE
    $ErrorActionPreference = $prev
    return $code
}

# Remove empty/nested .git folders so files are tracked in monorepo
$nestedGit = @(
    "$Root\V2.0\scp\.git"
)
foreach ($g in $nestedGit) {
    if (Test-Path $g) {
        Remove-Item -Recurse -Force $g
        Write-Host "Removed nested repo: $g"
    }
}

if (-not (Test-Path ".git")) {
    git init
    git branch -M main
}
if (-not (git remote get-url origin 2>$null)) {
    git remote add origin "https://github.com/ManStevoh/Sapphital_commerce_platform.git"
}

# Ordered component groups (directory -> commit message)
$components = @(
    @{ Path = "scripts"; Msg = "chore: add git automation scripts" },
    @{ Path = "V2.0/.github"; Msg = "chore: add V2.0 GitHub workflows" },
    @{ Path = "V2.0/README.md"; Msg = "docs: add V2.0 readme" },
    @{ Path = "V2.0/docs-site"; Msg = "docs: add documentation site" },
    @{ Path = "V2.0/docs/00-meta"; Msg = "docs: add engineering meta and ADRs" },
    @{ Path = "V2.0/docs/01-vision"; Msg = "docs: add product vision volume" },
    @{ Path = "V2.0/docs/02-market-research"; Msg = "docs: add market research volume" },
    @{ Path = "V2.0/docs/03-architecture"; Msg = "docs: add architecture volume" },
    @{ Path = "V2.0/docs/04-design-system"; Msg = "docs: add design system volume" },
    @{ Path = "V2.0/docs/05-commerce-engine"; Msg = "docs: add commerce engine volume" },
    @{ Path = "V2.0/docs/06-theme-engine"; Msg = "docs: add theme engine volume" },
    @{ Path = "V2.0/docs/07-cms"; Msg = "docs: add CMS volume" },
    @{ Path = "V2.0/docs/08-marketplace"; Msg = "docs: add marketplace volume" },
    @{ Path = "V2.0/docs/09-ai-platform"; Msg = "docs: add AI platform volume" },
    @{ Path = "V2.0/docs/10-infrastructure"; Msg = "docs: add infrastructure volume" },
    @{ Path = "V2.0/docs/11-security"; Msg = "docs: add security volume" },
    @{ Path = "V2.0/docs/12-developer-platform"; Msg = "docs: add developer platform volume" },
    @{ Path = "V2.0/docs/13-testing"; Msg = "docs: add testing volume" },
    @{ Path = "V2.0/docs/14-operations"; Msg = "docs: add operations volume" },
    @{ Path = "V2.0/docs/15-future-roadmap"; Msg = "docs: add future roadmap volume" },
    @{ Path = "V2.0/docs/16-saas-multi-tenancy"; Msg = "docs: add SaaS multi-tenancy volume" },
    @{ Path = "V2.0/docs/17-database-data-architecture"; Msg = "docs: add database architecture volume" },
    @{ Path = "V2.0/docs/18-mobile-pos"; Msg = "docs: add mobile POS volume" },
    @{ Path = "V2.0/docs/19-automation-integrations"; Msg = "docs: add automation integrations volume" },
    @{ Path = "V2.0/docs/20-legal-enterprise"; Msg = "docs: add legal enterprise volume" },
    @{ Path = "V2.0/docs/21-implementation-playbooks"; Msg = "docs: add implementation playbooks" }
)

# SCP platform packages
$scpDirs = Get-ChildItem "V2.0/scp" -Directory -ErrorAction SilentlyContinue | Sort-Object Name
foreach ($d in $scpDirs) {
    $name = $d.Name
    $components += @{ Path = "V2.0/scp/$name"; Msg = "feat: add SCP $name" }
}

$count = 0
foreach ($c in $components) {
    if (-not (Test-Path $c.Path)) { continue }

    Invoke-Git add -- "$($c.Path)" | Out-Null
    if ((Invoke-Git diff --cached --quiet) -eq 0) { continue }

    Invoke-Git commit -m $c.Msg | Out-Null

    $count++
    Write-Host "Committed: $($c.Msg)"

    if ($count % $PushEvery -eq 0) {
        Write-Host "Pushing batch..."
        Invoke-Git push origin main | Out-Null
    }
}

# Per-file commits for remaining untracked (max granularity)
$remaining = git ls-files --others --exclude-standard
foreach ($f in $remaining) {
    Invoke-Git add -- "$f" | Out-Null
    if ((Invoke-Git diff --cached --quiet) -eq 0) { continue }

    $norm = $f -replace '\\', '/'
    Invoke-Git commit -m "feat: add $norm" | Out-Null
    $count++
    if ($count % $PushEvery -eq 0) {
        Invoke-Git push origin main | Out-Null
    }
}

Get-Job | Wait-Job -ErrorAction SilentlyContinue | Out-Null
Write-Host "Final push..."
Invoke-Git push -u origin main | Out-Null
$total = git rev-list --count HEAD
Write-Host "Done. $count new commits. Total: $total"
