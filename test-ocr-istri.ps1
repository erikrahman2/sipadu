#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Test OCR enhancement for KTP_ISTRI
.DESCRIPTION
    Script untuk test OCR quality improvement dengan document-type awareness
.EXAMPLE
    .\test-ocr-istri.ps1 -ImagePath "path/to/ktp_istri.jpg"
#>

param(
    [Parameter(Mandatory=$true)]
    [string]$ImagePath,
    
    [Parameter(Mandatory=$false)]
    [string]$OCRServiceUrl = "http://localhost:5001",
    
    [Parameter(Mandatory=$false)]
    [string]$SecretKey = ""
)

# Colors for output
$Green = "`e[32m"
$Yellow = "`e[33m"
$Red = "`e[31m"
$Reset = "`e[0m"

function Write-Success {
    param([string]$Message)
    Write-Host "${Green}✓${Reset} $Message"
}

function Write-Info {
    param([string]$Message)
    Write-Host "${Yellow}ℹ${Reset} $Message"
}

function Write-Error {
    param([string]$Message)
    Write-Host "${Red}✗${Reset} $Message"
}

# Validate image file
if (-not (Test-Path $ImagePath)) {
    Write-Error "Image file not found: $ImagePath"
    exit 1
}

Write-Info "Testing OCR enhancement for KTP_ISTRI"
Write-Info "Image: $ImagePath"
Write-Info "Service URL: $OCRServiceUrl"
Write-Info "Document Type: KTP_ISTRI"
Write-Info ""

# Read image file
$imageBytes = [System.IO.File]::ReadAllBytes($ImagePath)
Write-Success "Image file loaded: $([math]::Round($imageBytes.Length / 1024, 2)) KB"

# Prepare multipart form data
$boundary = [System.Guid]::NewGuid().ToString()
$tempFile = [System.IO.Path]::GetTempFileName()

# Build request body
$body = New-Object System.Collections.ArrayList
$body.Add([System.Text.Encoding]::ASCII.GetBytes("--$boundary`r`n"))
$body.Add([System.Text.Encoding]::ASCII.GetBytes("Content-Disposition: form-data; name=`"file`"; filename=`"$(Split-Path $ImagePath -Leaf)`"`r`n"))
$body.Add([System.Text.Encoding]::ASCII.GetBytes("Content-Type: image/jpeg`r`n`r`n"))
$body.Add($imageBytes)
$body.Add([System.Text.Encoding]::ASCII.GetBytes("`r`n--$boundary--`r`n"))

$bodyBytes = $null
foreach ($part in $body) {
    if ($bodyBytes -eq $null) {
        $bodyBytes = $part
    } else {
        $bodyBytes += $part
    }
}

# Prepare headers
$headers = @{
    "X-OCR-Secret" = $SecretKey
    "X-Document-Type" = "KTP_ISTRI"
}

Write-Info "Sending OCR request with X-Document-Type: KTP_ISTRI..."

try {
    $response = Invoke-WebRequest `
        -Uri "$OCRServiceUrl/ocr/process" `
        -Method POST `
        -Headers $headers `
        -Body $bodyBytes `
        -ContentType "multipart/form-data; boundary=$boundary" `
        -TimeoutSec 60

    if ($response.StatusCode -ne 200) {
        Write-Error "OCR service returned status code: $($response.StatusCode)"
        exit 1
    }

    Write-Success "OCR request completed successfully"
    Write-Info ""

    # Parse response
    $result = $response.Content | ConvertFrom-Json

    # Display results
    Write-Info "OCR Extraction Results:"
    Write-Host ""
    Write-Host "  Field Confidence Scores:"
    $confidence = $result.confidence
    foreach ($field in @('nik', 'nama', 'tgl_lahir', 'tempat_lahir', 'alamat', 'rt_rw', 'kelurahan', 'kecamatan')) {
        $score = if ($confidence.$field) { $confidence.$field } else { 0 }
        $status = if ($score -ge 0.85) { "${Green}EXCELLENT${Reset}" } `
                  elseif ($score -ge 0.75) { "${Yellow}GOOD${Reset}" } `
                  else { "${Red}FAIR${Reset}" }
        Write-Host "  - $field`: $([math]::Round($score, 3)) [$status]"
    }

    Write-Host ""
    Write-Host "  Overall Confidence: $([math]::Round($result.overall_confidence, 3)) / 1.000"
    Write-Host "  OCR Status: $($result.ocr_status)"
    Write-Host "  Processing Time: $($result.processing_ms)ms"
    Write-Host ""

    # Display extracted data
    Write-Info "Extracted Data:"
    Write-Host ""
    Write-Host "  NIK: $($result.nik ?? '-')"
    Write-Host "  Nama: $($result.nama ?? '-')"
    Write-Host "  Tanggal Lahir: $($result.tgl_lahir ?? '-')"
    Write-Host "  Tempat Lahir: $($result.tempat_lahir ?? '-')"
    Write-Host "  Alamat: $($result.alamat ?? '-')"
    Write-Host "  RT/RW: $($result.rt_rw ?? '-')"
    Write-Host "  Kelurahan: $($result.kelurahan ?? '-')"
    Write-Host "  Kecamatan: $($result.kecamatan ?? '-')"
    Write-Host ""

    # Validation
    Write-Info "Validation:"
    $isValidNik = $result.nik -match '^\d{16}$'
    $isValidDate = $result.tgl_lahir -match '^\d{2}-\d{2}-\d{4}$'
    
    if ($isValidNik) {
        Write-Success "NIK format valid"
    } else {
        Write-Error "NIK format invalid"
    }
    
    if ($isValidDate) {
        Write-Success "Date format valid"
    } else {
        Write-Error "Date format invalid"
    }
    
    Write-Host ""
    Write-Success "Test completed successfully"

} catch {
    Write-Error "OCR request failed: $_"
    exit 1
}
