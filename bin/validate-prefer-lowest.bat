@echo off

SET app=%0
SET lib=%~dp0

php "%lib%validate-prefer-lowest.php" %*

echo.

exit /B %ERRORLEVEL%
