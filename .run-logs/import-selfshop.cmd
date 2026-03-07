@echo off
"C:\xampp\mysql\bin\mysql.exe" --host=127.0.0.1 --port=3306 --user=root selfshop < "%~1"
