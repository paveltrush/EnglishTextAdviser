CREATE DATABASE `adviser` CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'root'@'localhost' IDENTIFIED BY '12345';
GRANT ALL PRIVILEGES ON adviser.* TO 'root'@'localhost';
