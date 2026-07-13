#!/bin/bash
# Run this script to test password reset stress
# Usage: bash tests/k6/run-password-reset-test.sh

cd "$(dirname "$0")/../.."

echo "==> Generating fresh tokens..."
php artisan tinker tests/k6/generate-tokens.php

echo "==> Verifying tokens..."
php artisan tinker --execute 'echo DB::table("password_reset_tokens")->count() . " tokens ready";'

echo "==> Running k6 stress test..."
k6 run tests/k6/password-reset-test.js
