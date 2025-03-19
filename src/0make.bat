@echo off
set NAME=neo-webp-avif-converter
set FTP=x:\ftp\pub\Wordpress\%NAME%\snapshot

: 日付
for /f "tokens=2 delims==" %%I in ('"wmic os get localdatetime /value"') do set datetime=%%I
:DT=%datetime:~0,4%%datetime:~4,2%%datetime:~6,2%
copy ..\*.md .
copy ..\*.txt .
echo Snapshot %datetime:~0,14%

wsl 7z a -tzip -mx9 %NAME%-%datetime:~0,14%.zip *.bat *.sh *.php *.md *.txt

copy %NAME%-%datetime:~0,14%.zip %FTP%
del *.md
del *.txt
del %NAME%-%datetime:~0,14%.zip
pause
