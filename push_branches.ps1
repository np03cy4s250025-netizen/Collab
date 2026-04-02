# Script to push specific files to individual team branches without deleting anything

Write-Host "Switching to Ayush's branch..." -ForegroundColor Green
git checkout "feature/fron(Ayush)"
# Ayush's files: index.php, CSS, and JS
git add index.php
git add frontend/css/
git add frontend/js/
git commit -m "Push index.php, CSS, and JS for Ayush"
git push origin "feature/fron(Ayush)"

Write-Host "Switching to Khalid's branch..." -ForegroundColor Green
git checkout "feature/fron(Khalid)"
# Khalid's files: frontend pages and includes
git add frontend/pages/
git add frontend/includes/
git commit -m "Push pages and includes for Khalid"
git push origin "feature/fron(Khalid)"

Write-Host "Switching to Bidhusi's branch..." -ForegroundColor Green
git checkout "feature/back(bidhusi)"
# Bidhusi's files: backend models and utils
git add backend/models/
git add backend/utils/
git commit -m "Push models and utils for Bidhusi"
git push origin "feature/back(bidhusi)"

# Switch back to the base branch when completely done
Write-Host "Reverting back to base branch..." -ForegroundColor Green
git checkout "feature/back(biplov)"

Write-Host "All branch pushes completed successfully!" -ForegroundColor Green
Read-Host "Press Enter to close this window"
