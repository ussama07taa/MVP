# Start Laravel Backend in a new window
Write-Host "Starting Laravel Backend..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan serve --port=8001" -WindowStyle Normal

# Start Vite Frontend in a new window
Write-Host "Starting Vite Frontend..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "npm run dev" -WindowStyle Normal

Write-Host "Servers are starting in separate windows." -ForegroundColor Yellow
