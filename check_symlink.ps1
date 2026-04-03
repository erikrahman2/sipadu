if (Test-Path 'public\storage') {
    $linkInfo = Get-Item 'public\storage'
    if ($linkInfo.LinkType -eq 'SymbolicLink') {
        Write-Host "Symlink Target: $($linkInfo.Target)"
        Write-Host "Symlink is valid: YES"
    } else {
        Write-Host "public\storage is a DIRECTORY, not a symlink"
        Write-Host "Contents:" -ForegroundColor Yellow
        Get-ChildItem 'public\storage' -Force | Select-Object Name, LinkType
    }
} else {
    Write-Host "public\storage does not exist"
}

# Also check if the target files are accessible
Write-Host "`nChecking if files are accessible via storage disk:"
if (Test-Path 'public\storage\public_submissions\2') {
    Write-Host "public/storage/public_submissions/2 exists"
    Get-ChildItem 'public\storage\public_submissions\2' | Select-Object Name, Length
} else {
    Write-Host "public/storage/public_submissions/2 does NOT exist"
}
