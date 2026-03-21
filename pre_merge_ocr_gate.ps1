param(
    [string]$Endpoint = "http://127.0.0.1:5001",
    [string]$Secret = "change_me",
    [double]$MinOverallSimilarity = 95.0
)

$ErrorActionPreference = "Stop"

function Fail([string]$msg) {
    Write-Host "FAIL: $msg" -ForegroundColor Red
    exit 1
}

function Pass([string]$msg) {
    Write-Host "PASS: $msg" -ForegroundColor Green
}

Write-Host "Running OCR pre-merge gate..." -ForegroundColor Cyan

# 1) Syntax checks
& "D:\laragon\bin\python\python-3.10\python.exe" -m py_compile "d:\ProyekTA\ocr-service\app.py"
if ($LASTEXITCODE -ne 0) { Fail "Python syntax check failed for ocr-service/app.py" }
Pass "Python syntax check"

php -l "d:\ProyekTA\app\Services\OCRService.php" | Out-Null
if ($LASTEXITCODE -ne 0) { Fail "PHP lint failed for app/Services/OCRService.php" }
Pass "PHP lint check"

# 2) Health check
$health = Invoke-RestMethod -Uri "$Endpoint/health" -Headers @{"X-OCR-Secret" = $Secret}
if (-not $health.ready) { Fail "OCR health not ready" }
if ($health.missing_langs.Count -gt 0) { Fail "Missing OCR language packs: $($health.missing_langs -join ', ')" }
Pass "OCR runtime health ready"

# 3) Batch compare
Push-Location "d:\ProyekTA\ocr-accuracy-test"
try {
    $csvPath = "d:\ProyekTA\ocr-accuracy-test\results\batch_metrics_gate.csv"

    & "D:\laragon\bin\python\python-3.10\python.exe" batch_compare.py `
        --image-dir "sample_data\images" `
        --ground-truth "sample_data\ground_truth_template.json" `
        --endpoint "$Endpoint/ocr/process" `
        --secret $Secret `
        --output-csv $csvPath

    if ($LASTEXITCODE -ne 0) { Fail "batch_compare.py failed" }

    if (-not (Test-Path $csvPath)) { Fail "CSV report not found: $csvPath" }

    $rows = Import-Csv $csvPath
    if (-not $rows -or $rows.Count -eq 0) { Fail "CSV report is empty" }

    $overall = ($rows | Measure-Object -Property similarity -Average).Average
    if ($overall -lt $MinOverallSimilarity) {
        Fail ("Overall similarity too low: {0:N2}% < {1:N2}%" -f $overall, $MinOverallSimilarity)
    }

    $critical = $rows | Where-Object {
        $_.image -eq "sample (41).jpg" -and ($_.field -eq "nik" -or $_.field -eq "tanggal_lahir")
    }

    if ($critical.Count -ne 2) { Fail "Critical rows for sample (41).jpg not found" }

    foreach ($r in $critical) {
        $sim = [double]$r.similarity
        if ($sim -lt 90.0) {
            Fail ("Critical field failed: sample (41).jpg / {0} similarity={1:N2}%" -f $r.field, $sim)
        }
    }

    Pass ("Batch gate passed with overall similarity {0:N2}%" -f $overall)
}
finally {
    Pop-Location
}

Write-Host "OCR pre-merge gate PASSED. Safe to merge." -ForegroundColor Green
exit 0
