@echo off
title Jason111nn Project Setup
echo [INFO] Starting Environment Restoration...

echo [1/2] Restoring Vue 3 dependencies...
cd 00_module_b_vue
call npm install
echo [1/2] Vue restoration complete.
cd ..

echo [2/2] Restoring Laravel 9 dependencies (PHP 8.0)...
cd 00_module_d_laravel

call composer install --ignore-platform-reqs --no-security-blocking
echo [2/2] Laravel restoration complete.
cd ..

echo [SUCCESS] All modules are ready to run.
pause
