param(
    [string]$ServiceUrl = "http://127.0.0.1:5001",
    [string]$TesseractCmd = "C:\Program Files\Tesseract-OCR\tesseract.exe",
    [string]$ExpectedLang = "ind",
    [string]$TessdataPrefix = "D:\ProyekTA\ocr-service\tessdata"
)

$ErrorActionPreference = "Stop"
$script:checks = @()

function Add-Check {
    param(
        [string]$Name,
        [bool]$Passed,
        [string]$Detail
    )

    $script:checks += [PSCustomObject]@{
        Name = $Name
        Passed = $Passed
        Detail = $Detail
    }
}

try {
    if (Test-Path $TesseractCmd) {
        $ver = & $TesseractCmd --version 2>$null | Select-Object -First 1
        Add-Check -Name "Tesseract binary" -Passed $true -Detail $ver
    }
    else {
        Add-Check -Name "Tesseract binary" -Passed $false -Detail "Not found at $TesseractCmd"
    }
}
catch {
    Add-Check -Name "Tesseract binary" -Passed $false -Detail $_.Exception.Message
}

try {
    $env:TESSDATA_PREFIX = $TessdataPrefix
    $langs = & $TesseractCmd --list-langs 2>$null | Select-Object -Skip 1
    $hasLang = $langs -contains $ExpectedLang
    Add-Check -Name "Language pack" -Passed $hasLang -Detail (("TESSDATA_PREFIX=$TessdataPrefix; Available: " + (($langs -join ", "))))
}
catch {
    Add-Check -Name "Language pack" -Passed $false -Detail $_.Exception.Message
}

try {
    $health = Invoke-RestMethod -Uri "$ServiceUrl/health" -TimeoutSec 5
    $ready = $health.ready -eq $true
    Add-Check -Name "Service health" -Passed $ready -Detail ($health | ConvertTo-Json -Compress)
}
catch {
    Add-Check -Name "Service health" -Passed $false -Detail $_.Exception.Message
}

$script:checks | Format-Table -AutoSize

$failed = $script:checks | Where-Object { -not $_.Passed }
if ($failed.Count -gt 0) {
    Write-Host "`nOCR readiness: FAILED" -ForegroundColor Red
    exit 1
}

Write-Host "`nOCR readiness: PASSED" -ForegroundColor Green
exit 0
