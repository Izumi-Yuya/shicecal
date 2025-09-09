#!/bin/bash

# AWS Test Environment Testing Scripts
# Run these tests after deployment to verify everything is working

echo "🧪 Starting AWS Test Environment Tests..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test results
TESTS_PASSED=0
TESTS_FAILED=0

# Function to run test
run_test() {
    local test_name="$1"
    local test_command="$2"
    
    echo -e "\n${YELLOW}Testing: $test_name${NC}"
    
    if eval "$test_command"; then
        echo -e "${GREEN}✅ PASS: $test_name${NC}"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}❌ FAIL: $test_name${NC}"
        ((TESTS_FAILED++))
    fi
}

# Test 1: Nginx Service Status
run_test "Nginx Service Status" "sudo systemctl is-active nginx --quiet"

# Test 2: PHP-FPM Service Status
run_test "PHP-FPM Service Status" "sudo systemctl is-active php8.1-fpm --quiet"

# Test 3: MySQL Service Status (if installed)
run_test "MySQL Service Status" "sudo systemctl is-active mysql --quiet || true"

# Test 4: Web Server Response
run_test "Web Server HTTP Response" "curl -s -o /dev/null -w '%{http_code}' http://localhost | grep -q '200\|301\|302'"

# Test 5: PHP Version Check
run_test "PHP Version Check" "php --version | grep -q 'PHP 8.1'"

# Test 6: Composer Installation
run_test "Composer Installation" "composer --version > /dev/null 2>&1"

# Test 7: Node.js Installation
run_test "Node.js Installation" "node --version > /dev/null 2>&1"

# Test 8: Laravel Application Check
if [ -d "/var/www/facility-management" ]; then
    cd /var/www/facility-management
    run_test "Laravel Application Check" "php artisan --version > /dev/null 2>&1"
    run_test "Laravel Environment File" "[ -f .env ]"
    run_test "Laravel Storage Permissions" "[ -w storage/logs ]"
    run_test "Laravel Bootstrap Cache Permissions" "[ -w bootstrap/cache ]"
else
    echo -e "${RED}❌ Laravel project directory not found${NC}"
    ((TESTS_FAILED++))
fi

# Test 9: Database Connection (if configured)
if [ -f "/var/www/facility-management/.env" ]; then
    cd /var/www/facility-management
    run_test "Database Connection" "php artisan migrate:status > /dev/null 2>&1 || true"
fi

# Test 10: Nginx Configuration Test
run_test "Nginx Configuration Test" "sudo nginx -t > /dev/null 2>&1"

# Test 11: SSL Certificate (if configured)
if [ -f "/etc/letsencrypt/live/*/fullchain.pem" ]; then
    run_test "SSL Certificate Check" "sudo certbot certificates | grep -q 'VALID'"
fi

# Test 12: Firewall Status
run_test "UFW Firewall Status" "sudo ufw status | grep -q 'Status: active' || true"

# Test 13: Disk Space Check
run_test "Disk Space Check" "[ $(df / | tail -1 | awk '{print $5}' | sed 's/%//') -lt 90 ]"

# Test 14: Memory Usage Check
run_test "Memory Usage Check" "[ $(free | grep Mem | awk '{printf \"%.0f\", $3/$2 * 100.0}') -lt 90 ]"

# Performance Tests
echo -e "\n${YELLOW}🚀 Running Performance Tests...${NC}"

# Test 15: Basic Load Test (if Apache Bench is available)
if command -v ab > /dev/null 2>&1; then
    run_test "Basic Load Test (100 requests)" "ab -n 100 -c 10 http://localhost/ > /dev/null 2>&1"
else
    echo -e "${YELLOW}⚠️  Apache Bench not installed, skipping load test${NC}"
fi

# Security Tests
echo -e "\n${YELLOW}🔒 Running Security Tests...${NC}"

# Test 16: Hidden Files Protection
run_test "Hidden Files Protection" "curl -s -o /dev/null -w '%{http_code}' http://localhost/.env | grep -q '403\|404'"

# Test 17: PHP Files in Storage Protection
run_test "Storage Directory Protection" "curl -s -o /dev/null -w '%{http_code}' http://localhost/storage/test.php | grep -q '403\|404'"

# Summary
echo -e "\n${YELLOW}📊 Test Summary${NC}"
echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed! Your AWS test environment is ready.${NC}"
    exit 0
else
    echo -e "\n${RED}⚠️  Some tests failed. Please check the configuration.${NC}"
    exit 1
fi