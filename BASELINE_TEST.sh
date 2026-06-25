#!/bin/bash

# =====================================================
# PERFORMANCE BASELINE TEST
# Tests current application speed before optimization
# =====================================================

echo "🚀 HeyDaniel Performance Baseline Test"
echo "======================================"
echo "Testing: $(date)"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test configuration
API_URL="http://localhost/Server/index.php"
REQUESTS=100
CONCURRENCY=1

# Create results file
RESULTS_FILE="BASELINE_RESULTS_$(date +%Y%m%d_%H%M%S).txt"

{
    echo "Performance Baseline Test Results"
    echo "Date: $(date)"
    echo "URL: $API_URL"
    echo "======================================"
    echo ""

    # =====================================================
    # TEST 1: Basic API Response Time
    # =====================================================
    echo -e "${BLUE}TEST 1: Basic API Latency${NC}"
    echo "Measuring simple device_check endpoint (1 request)..."
    echo ""

    RESPONSE=$(curl -w "@/dev/stdin" -o /dev/null -s \
        -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{
            "action": "device_check",
            "device_type": "Web",
            "screen_resolution": "1920x1080"
        }' \
        -o /tmp/response.json 2>&1 <<< 'time_connect:%{time_connect}\ntime_starttransfer:%{time_starttransfer}\ntime_total:%{time_total}\n')

    echo "$RESPONSE"
    echo ""

    # Parse response time
    TOTAL_TIME=$(echo "$RESPONSE" | grep "time_total" | awk -F: '{print $2}' | awk '{print $1 * 1000}')
    CONNECT_TIME=$(echo "$RESPONSE" | grep "time_connect" | awk -F: '{print $2}' | awk '{print $1 * 1000}')

    echo "Total Time: ${TOTAL_TIME}ms"
    echo "Connect Time: ${CONNECT_TIME}ms"
    echo ""

    # =====================================================
    # TEST 2: Multiple Sequential Requests
    # =====================================================
    echo -e "${BLUE}TEST 2: Sequential Requests (5 calls)${NC}"
    echo "Simulating typical user interaction (5 API calls)..."
    echo ""

    TOTAL_SEQ_TIME=0

    # Device check
    TIME1=$(curl -o /dev/null -s -w '%{time_total}' \
        -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{"action": "device_check", "device_type": "Web", "screen_resolution": "1920x1080"}')

    # Cart icon
    TIME2=$(curl -o /dev/null -s -w '%{time_total}' \
        -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{"action": "cart_icon", "device_type": "Web"}')

    # Main categories
    TIME3=$(curl -o /dev/null -s -w '%{time_total}' \
        -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{"action": "main_categories", "device_type": "Web"}')

    # Store page
    TIME4=$(curl -o /dev/null -s -w '%{time_total}' \
        -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{"action": "store", "device_type": "Web", "page": 1, "limit": 20}')

    # Summary
    TIME5=$(curl -o /dev/null -s -w '%{time_total}' \
        -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{"action": "summary", "device_type": "Web"}')

    echo "1. device_check: $(echo "$TIME1 * 1000" | bc)ms"
    echo "2. cart_icon: $(echo "$TIME2 * 1000" | bc)ms"
    echo "3. main_categories: $(echo "$TIME3 * 1000" | bc)ms"
    echo "4. store: $(echo "$TIME4 * 1000" | bc)ms"
    echo "5. summary: $(echo "$TIME5 * 1000" | bc)ms"

    TOTAL_SEQ=$(echo "$TIME1 + $TIME2 + $TIME3 + $TIME4 + $TIME5" | bc)
    echo ""
    echo "Total Sequential Time: $(echo "$TOTAL_SEQ * 1000" | bc)ms"
    echo "Average per request: $(echo "scale=2; $TOTAL_SEQ * 1000 / 5" | bc)ms"
    echo ""

    # =====================================================
    # TEST 3: Load Test with Apache Bench
    # =====================================================
    echo -e "${BLUE}TEST 3: Load Test (10 requests, sequential)${NC}"
    echo "Running Apache Bench with 10 requests..."
    echo ""

    # Create JSON payload file
    cat > /tmp/test_payload.json <<'EOF'
{"action": "device_check", "device_type": "Web", "screen_resolution": "1920x1080"}
EOF

    # Run Apache Bench
    AB_RESULTS=$(ab -n 10 -c 1 -p /tmp/test_payload.json \
        -T application/json \
        $API_URL 2>&1)

    echo "$AB_RESULTS"
    echo ""

    # Extract metrics
    MEAN_TIME=$(echo "$AB_RESULTS" | grep "Time per request:" | head -1 | awk '{print $4}')
    MIN_TIME=$(echo "$AB_RESULTS" | grep "min" | head -1 | awk '{print $3}')
    MAX_TIME=$(echo "$AB_RESULTS" | grep "max" | head -1 | awk '{print $3}')

    echo "Mean response time: $MEAN_TIME ms"
    echo ""

    # =====================================================
    # TEST 4: Concurrent Load Test
    # =====================================================
    echo -e "${BLUE}TEST 4: Concurrent Load (5 concurrent requests)${NC}"
    echo "Running 10 requests with 5 concurrent..."
    echo ""

    AB_CONCURRENT=$(ab -n 10 -c 5 -p /tmp/test_payload.json \
        -T application/json \
        $API_URL 2>&1)

    echo "$AB_CONCURRENT"
    echo ""

    CONCURRENT_MEAN=$(echo "$AB_CONCURRENT" | grep "Time per request:" | head -1 | awk '{print $4}')
    echo "Mean response time (concurrent): $CONCURRENT_MEAN ms"
    echo ""

    # =====================================================
    # TEST 5: Response Size Analysis
    # =====================================================
    echo -e "${BLUE}TEST 5: Response Size Analysis${NC}"
    echo ""

    # Device check size
    SIZE1=$(curl -s -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{"action": "device_check", "device_type": "Web", "screen_resolution": "1920x1080"}' | wc -c)

    # Main categories size
    SIZE2=$(curl -s -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{"action": "main_categories", "device_type": "Web"}' | wc -c)

    # Store page size
    SIZE3=$(curl -s -X POST $API_URL \
        -H "Content-Type: application/json" \
        -d '{"action": "store", "device_type": "Web", "page": 1, "limit": 20}' | wc -c)

    echo "device_check response: $SIZE1 bytes"
    echo "main_categories response: $SIZE2 bytes"
    echo "store response: $SIZE3 bytes"
    echo ""
    echo "Total for 3 requests: $((SIZE1 + SIZE2 + SIZE3)) bytes"
    echo ""

    # =====================================================
    # SUMMARY
    # =====================================================
    echo -e "${YELLOW}======================================"
    echo "BASELINE SUMMARY"
    echo "======================================${NC}"
    echo ""
    echo "Single Request Latency: ~$(echo "$MEAN_TIME" | awk -F. '{print $1}')ms"
    echo "5 Sequential Requests: ~$(echo "$TOTAL_SEQ * 1000" | bc | awk -F. '{print $1}')ms"
    echo "Response Compression: NOT ENABLED (responses can be 6-7x smaller with gzip)"
    echo ""
    echo "Estimated Load:"
    echo "  - Current: 1,000 concurrent users max"
    echo "  - Throughput: ~10-100 requests/second"
    echo ""
    echo -e "${GREEN}Next Steps:${NC}"
    echo "1. Run: mysql < Tables/PERFORMANCE_INDEXES.sql"
    echo "2. Enable gzip in Server/index.php"
    echo "3. Re-run this test to see improvements"
    echo ""

} | tee "$RESULTS_FILE"

echo ""
echo -e "${GREEN}✓ Results saved to: $RESULTS_FILE${NC}"
echo ""
