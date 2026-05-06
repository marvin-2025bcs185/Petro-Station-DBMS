@echo off
cd /d C:\xampp\mysql\bin
start "" mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --port=3307 --standalone
echo XAMPP MySQL started on port 3307.
echo You can now open http://localhost/petrostation/
pause
