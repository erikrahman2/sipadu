$token = 'PUB-MQMQPX8OD0JKTAKKKM8Z'
$url = 'http://localhost/api/v1/tracking/' + $token
try {
    $response = Invoke-WebRequest -Uri $url -UseBasicParsing
    $json = $response.Content | ConvertFrom-Json
    Write-Host "API Response Status: OK" -ForegroundColor Green
    Write-Host "Type: $($json.type)"
    Write-Host "Documents Count: $($json.documents_count)"
    if ($json.documents.Count -gt 0) {
        Write-Host "First Document URL: $($json.documents[0].url)"
        Write-Host "All Document URLs:"
        foreach ($doc in $json.documents) {
            Write-Host "  - $($doc.url)"
        }
    }
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}
