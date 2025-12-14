@echo off
REM MQTT Setup Script for Windows

echo.
echo ========================================
echo MQTT Production Monitoring Setup
echo ========================================
echo.

REM Check if Docker is installed
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker is not installed or not in PATH
    echo Please install Docker Desktop from https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

echo [OK] Docker found
echo.

REM Check if docker-compose.yml exists
if not exist docker-compose.yml (
    echo [ERROR] docker-compose.yml not found
    echo Please run this script from project root directory
    pause
    exit /b 1
)

echo [OK] docker-compose.yml found
echo.

REM Start MQTT broker
echo Starting MQTT broker...
docker-compose up -d mosquitto

if %errorlevel% neq 0 (
    echo [ERROR] Failed to start MQTT broker
    pause
    exit /b 1
)

echo [OK] MQTT broker started
echo.

REM Wait for broker to be ready
echo Waiting for MQTT broker to be ready...
timeout /t 3 /nobreak

REM Test connection
echo Testing MQTT connection...
php artisan mqtt:debug test

if %errorlevel% neq 0 (
    echo [WARNING] Connection test failed
    echo Please check MQTT_TROUBLESHOOTING.md
    pause
    exit /b 1
)

echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Start listener in a new terminal:
echo    php artisan mqtt:production-listener
echo.
echo 2. Test in another terminal:
echo    docker exec mosquitto mosquitto_pub -h localhost -t "production/1/qty_ok" -m "{\"monitoring_id\":1,\"qty\":1}"
echo.
echo 3. Open browser:
echo    http://localhost/production/production-monitoring
echo.
echo For help, see:
echo - MQTT_QUICK_REFERENCE.md
echo - MQTT_SETUP_CHECKLIST.md
echo - MQTT_TROUBLESHOOTING.md
echo.
pause
