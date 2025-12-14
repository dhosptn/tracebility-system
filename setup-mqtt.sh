#!/bin/bash

# MQTT Setup Script for Linux

echo ""
echo "========================================"
echo "MQTT Production Monitoring Setup"
echo "========================================"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "[ERROR] Docker is not installed"
    echo "Please install Docker from https://docs.docker.com/engine/install/"
    exit 1
fi

echo "[OK] Docker found"
echo ""

# Check if docker-compose.yml exists
if [ ! -f docker-compose.yml ]; then
    echo "[ERROR] docker-compose.yml not found"
    echo "Please run this script from project root directory"
    exit 1
fi

echo "[OK] docker-compose.yml found"
echo ""

# Start MQTT broker
echo "Starting MQTT broker..."
docker-compose up -d mosquitto

if [ $? -ne 0 ]; then
    echo "[ERROR] Failed to start MQTT broker"
    exit 1
fi

echo "[OK] MQTT broker started"
echo ""

# Wait for broker to be ready
echo "Waiting for MQTT broker to be ready..."
sleep 3

# Test connection
echo "Testing MQTT connection..."
php artisan mqtt:debug test

if [ $? -ne 0 ]; then
    echo "[WARNING] Connection test failed"
    echo "Please check MQTT_TROUBLESHOOTING.md"
    exit 1
fi

echo ""
echo "========================================"
echo "Setup Complete!"
echo "========================================"
echo ""
echo "Next steps:"
echo "1. Start listener in a new terminal:"
echo "   php artisan mqtt:production-listener"
echo ""
echo "2. Test in another terminal:"
echo "   docker exec mosquitto mosquitto_pub -h localhost -t \"production/1/qty_ok\" -m '{\"monitoring_id\":1,\"qty\":1}'"
echo ""
echo "3. Open browser:"
echo "   http://localhost/production/production-monitoring"
echo ""
echo "For help, see:"
echo "- MQTT_QUICK_REFERENCE.md"
echo "- MQTT_SETUP_CHECKLIST.md"
echo "- MQTT_TROUBLESHOOTING.md"
echo ""
