CREATE DATABASE formstack;
CREATE USER 'formstack'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON * . * TO 'formstack'@'localhost';
FLUSH PRIVILEGES;