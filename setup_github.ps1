# Setup Github Script for SpinGo_Preview_Clean
Write-Host "Initializing Git..." -ForegroundColor Green
git init

Write-Host "Adding remote origin..." -ForegroundColor Green
git remote add origin https://github.com/np03cy4s250025-netizen/Collab.git
git fetch origin

Write-Host "Creating main branch feature/back(biplov)..." -ForegroundColor Green
git checkout -b "feature/back(biplov)"

Write-Host "Adding backend config folder..." -ForegroundColor Green
git add backend/config/
git commit -m "Initialize config configurations"

Write-Host "Pushing feature/back(biplov)..." -ForegroundColor Green
git push -u origin "feature/back(biplov)"

Write-Host "Creating other requested branches..." -ForegroundColor Green
git checkout -b "feature/back(bidhusi)"
git push -u origin "feature/back(bidhusi)"

git checkout -b "feature/fron(Khalid)"
git push -u origin "feature/fron(Khalid)"

git checkout -b "feature/fron(Ayush)"
git push -u origin "feature/fron(Ayush)"

Write-Host "Scanning and deleting old untracked branches from origin..." -ForegroundColor Yellow
# Find and remove branches from remote that do not match the new branches
$branchesToRemove = git branch -r | Select-String -NotMatch "(feature/back\(biplov\)|feature/back\(bidhusi\)|feature/fron\(Khalid\)|feature/fron\(Ayush\)|HEAD)"

foreach ($branchObj in $branchesToRemove) {
    $branchName = $branchObj.Line.Trim() -replace "^origin/", ""
    if (![string]::IsNullOrEmpty($branchName)) {
        Write-Host "Deleting remote old branch: $branchName" -ForegroundColor Red
        git push origin --delete $branchName
    }
}

Write-Host "Returning to working branch feature/back(biplov)..." -ForegroundColor Green
git checkout "feature/back(biplov)"

Write-Host "All tasks completed successfully!" -ForegroundColor Green
Read-Host "Press Enter to close this window"
