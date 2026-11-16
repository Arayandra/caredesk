@echo off
setlocal EnableExtensions

set "REPO_URL=https://github.com/Arayandra/caredesk.git"
set "BRANCH=main"
for %%I in ("%~dp0.") do set "SOURCE_DIR=%%~fI"
set "WORK_DIR=%TEMP%\caredesk-publish-%RANDOM%-%RANDOM%"

echo ============================================
echo  Publish CareDesk to GitHub
echo ============================================

where git >nul 2>nul
if errorlevel 1 (
  echo ERROR: Git is not installed or not available in PATH.
  pause
  exit /b 1
)

if not exist "%SOURCE_DIR%\artisan" (
  echo ERROR: artisan was not found. Run this script in the project root.
  pause
  exit /b 1
)

echo [1/4] Cloning repository...
git clone --branch "%BRANCH%" --single-branch "%REPO_URL%" "%WORK_DIR%"
if errorlevel 1 (
  echo ERROR: Clone failed. Check repository URL, network, and GitHub authentication.
  pause
  exit /b 1
)

echo [2/4] Copying project files...
robocopy "%SOURCE_DIR%" "%WORK_DIR%" /E /XD .git vendor node_modules .idea .vscode /XF .env auth.json *.log *.sqlite *.sqlite3 *.pyc
set "COPY_RESULT=%ERRORLEVEL%"
if %COPY_RESULT% GEQ 8 (
  echo ERROR: Robocopy failed with exit code %COPY_RESULT%.
  pause
  exit /b 1
)

echo [3/4] Creating commit...
git -C "%WORK_DIR%" add -A
git -C "%WORK_DIR%" diff --cached --quiet
if not errorlevel 1 (
  echo No file changes to publish. Repository is already up to date.
  echo %REPO_URL%
  pause
  exit /b 0
)
git -C "%WORK_DIR%" -c core.autocrlf=true commit -m "Publish CareDesk Laravel Vue application"
if errorlevel 1 (
  echo ERROR: Commit failed. Configure git user.name and user.email if needed.
  pause
  exit /b 1
)

echo [4/4] Pushing to GitHub...
git -C "%WORK_DIR%" push -u origin "%BRANCH%"
if errorlevel 1 (
  echo ERROR: Push failed. Check GitHub authentication and branch permissions.
  pause
  exit /b 1
)

echo.
echo SUCCESS: CareDesk has been pushed to GitHub.
echo %REPO_URL%
pause
exit /b 0
