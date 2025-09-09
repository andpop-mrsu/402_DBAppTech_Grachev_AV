@echo off
chcp 65001 >nul
set DB_FILE=self_logger.db

if not exist %DB_FILE% (
    sqlite3 %DB_FILE% "CREATE TABLE log(id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, datetime TEXT);"
)

set USERNAME=%USERNAME%
for /f "tokens=1-2 delims= " %%a in ('echo %date% %time%') do set DATETIME=%%a %%b

sqlite3 %DB_FILE% "INSERT INTO log(username, datetime) VALUES('%USERNAME%', '%DATETIME%');"

echo Имя программы: self-logger.bat
for /f "tokens=1" %%c in ('sqlite3 %DB_FILE% "SELECT COUNT(*) FROM log;"') do set COUNT=%%c
echo Количество запусков: %COUNT%
for /f "tokens=1" %%d in ('sqlite3 %DB_FILE% "SELECT MIN(datetime) FROM log;"') do set FIRST=%%d
echo Первый запуск: %FIRST%
echo ---------------------------------------------
echo User      ^| Date
echo ---------------------------------------------
sqlite3 %DB_FILE% "SELECT username || ' | ' || datetime FROM log;"
pause