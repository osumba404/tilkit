# Creates GitHub milestones, labels, issues, and a Project board for the TILKIT roadmap.
# Requires: GitHub CLI (gh) authenticated - run: gh auth login
# Usage (from repo root):
#   powershell -ExecutionPolicy Bypass -File .github/tilkit-project/create-github-project.ps1

$ErrorActionPreference = "Stop"
$RepoRoot = Split-Path (Split-Path $PSScriptRoot -Parent) -Parent
if (-not (Test-Path (Join-Path $RepoRoot ".git"))) {
    $RepoRoot = Split-Path $PSScriptRoot -Parent
    while ($RepoRoot -and -not (Test-Path (Join-Path $RepoRoot ".git"))) {
        $RepoRoot = Split-Path $RepoRoot -Parent
    }
}
Set-Location $RepoRoot

$Gh = Get-Command gh -ErrorAction SilentlyContinue
if (-not $Gh) {
    $GhPath = "${env:ProgramFiles}\GitHub CLI\gh.exe"
    if (Test-Path $GhPath) { $Gh = $GhPath } else {
        Write-Error "GitHub CLI (gh) not found. Install: winget install GitHub.cli - then run: gh auth login"
    }
}

function Invoke-Gh {
    param([string[]]$Args)
    if ($Gh -is [string]) { & $Gh @Args } else { & gh @Args }
}

$remote = (git remote get-url origin 2>$null)
if ($remote -match "github\.com[:/](?<owner>[^/]+)/(?<repo>[^/.]+)") {
    $Owner = $Matches.owner
    $Repo = $Matches.repo
} else {
    Write-Error "Could not parse GitHub owner/repo from origin: $remote"
}

Write-Host "Repository: $Owner/$Repo"
Invoke-Gh @("auth", "status") | Out-Null

$labels = @(
    @{ name = "tilkit"; color = "0a7c94"; description = "TILKIT indicator platform work" },
    @{ name = "epic"; color = "1e293b"; description = "Epic / phase umbrella issue" },
    @{ name = "phase-0"; color = "64748b"; description = "Foundation" },
    @{ name = "phase-1"; color = "64748b"; description = "Schema" },
    @{ name = "phase-2"; color = "64748b"; description = "Rules engine" },
    @{ name = "phase-3"; color = "64748b"; description = "Mobile + sync" },
    @{ name = "phase-4"; color = "64748b"; description = "Workshop + validation" },
    @{ name = "phase-5"; color = "64748b"; description = "Integration + v4 modules" },
    @{ name = "phase-6"; color = "64748b"; description = "Migration + pilots" },
    @{ name = "engine"; color = "08697d"; description = "Forecast engine" },
    @{ name = "mobile"; color = "475569"; description = "Field mobile app" },
    @{ name = "pilot"; color = "334155"; description = "Pilot deployment" }
)

foreach ($l in $labels) {
    Invoke-Gh @(
        "label", "ensure",
        "--repo", "$Owner/$Repo",
        "--name", $l.name,
        "--color", $l.color,
        "--description", $l.description
    ) 2>$null | Out-Null
    Write-Host "Label: $($l.name)"
}

$milestones = @(
    "Phase 0 - Foundation",
    "Phase 1 - Schema",
    "Phase 2 - Engine",
    "Phase 3 - Mobile",
    "Phase 4 - Workshop",
    "Phase 5 - Integration",
    "Phase 6 - Pilots"
)

foreach ($m in $milestones) {
    $existing = Invoke-Gh @("api", "repos/$Owner/$Repo/milestones", "--jq", ".[].title") 2>$null
    if ($existing -notcontains $m) {
        Invoke-Gh @(
            "api", "-X", "POST", "repos/$Owner/$Repo/milestones",
            "-f", "title=$m",
            "-f", "state=open"
        ) | Out-Null
        Write-Host "Milestone: $m"
    }
}

$issuesPath = Join-Path $PSScriptRoot "issues.json"
$issues = Get-Content $issuesPath -Raw | ConvertFrom-Json
$createdUrls = @()

foreach ($issue in $issues) {
    $labelArgs = @()
    foreach ($lab in $issue.labels) {
        $labelArgs += "--label"
        $labelArgs += $lab
    }
    $milestoneArg = @("--milestone", $issue.milestone)

    $bodyFile = [System.IO.Path]::GetTempFileName()
    Set-Content -Path $bodyFile -Value $issue.body -Encoding UTF8

    $out = Invoke-Gh @(
        "issue", "create",
        "--repo", "$Owner/$Repo",
        "--title", $issue.title,
        "--body-file", $bodyFile
    ) + $labelArgs + $milestoneArg

    Remove-Item $bodyFile -Force
    Write-Host $out
    if ($out -match "https://github.com/.+/issues/\d+") {
        $createdUrls += $Matches[0]
    }
}

$projectTitle = "TILKIT Platform Roadmap"
Write-Host "`nCreating project board: $projectTitle"

$user = Invoke-Gh @("api", "user", "--jq", ".login")
$projectJson = Invoke-Gh @(
    "project", "create",
    "--owner", $user,
    "--title", $projectTitle,
    "--format", "json"
)
$project = $projectJson | ConvertFrom-Json
$projectNumber = $project.number
Write-Host "Project #$projectNumber created for user $user"

Invoke-Gh @(
    "project", "field-create", $projectNumber,
    "--owner", $user,
    "--name", "Phase",
    "--data-type", "single_select",
    "--single-select-options", "Phase 0",
    "--single-select-options", "Phase 1",
    "--single-select-options", "Phase 2",
    "--single-select-options", "Phase 3",
    "--single-select-options", "Phase 4",
    "--single-select-options", "Phase 5",
    "--single-select-options", "Phase 6"
) 2>$null | Out-Null

foreach ($url in $createdUrls) {
    Invoke-Gh @(
        "project", "item-add", $projectNumber,
        "--owner", $user,
        "--url", $url
    ) | Out-Null
}

$projectUrl = "https://github.com/users/$user/projects/$projectNumber"
Write-Host "`nDone."
Write-Host "Project board: $projectUrl"
Write-Host "Issues created: $($createdUrls.Count)"
Write-Host "`nTip: Link repo to project in GitHub UI (Project Settings) if items should appear under $Owner/$Repo."
