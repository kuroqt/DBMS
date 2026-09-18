<?php
$host = 'localhost';
$dbname = 'dbms_db';
$username = 'root';
$password = 'Gordoncillo2005';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    // 1. Users / Credentials Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role VARCHAR(50) DEFAULT 'Medical Staff',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Auto-create default Doctor/Admin account if table is empty
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($user_count == 0) {
        $default_username = 'admin';
        $default_password = password_hash('password123', PASSWORD_DEFAULT); // Secure hashed password
        $default_name = 'Dr. Sarah Jenkins';
        $default_role = 'Administrator';

        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$default_username, $default_password, $default_name, $default_role]);
    }

    // 2. Employees table
    $pdo->exec("CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emp_code VARCHAR(20) NOT NULL UNIQUE,
        title VARCHAR(20) DEFAULT '',
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        dob DATE,
        sex VARCHAR(10),
        position VARCHAR(100),
        department VARCHAR(100),
        contact_number VARCHAR(20),
        status VARCHAR(20) DEFAULT 'Active',
        blood_type VARCHAR(5),
        allergies TEXT,
        medical_conditions TEXT,
        current_medication TEXT,
        emergency_contact_name VARCHAR(100),
        emergency_contact_number VARCHAR(20)
    )");

    // 3. Employee Consultations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS consultations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        consultation_date DATE NOT NULL,
        chief_complaint TEXT,
        diagnosis TEXT,
        remarks TEXT,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    // 4. Students table
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id_code VARCHAR(50) NOT NULL UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        middle_name VARCHAR(50) DEFAULT '',
        last_name VARCHAR(50) NOT NULL,
        dob DATE,
        sex VARCHAR(10),
        course VARCHAR(100),
        year_level VARCHAR(20),
        section VARCHAR(10),
        contact_number VARCHAR(20),
        status VARCHAR(20) DEFAULT 'Active',
        blood_type VARCHAR(5),
        allergies TEXT,
        medical_conditions TEXT,
        current_medication TEXT,
        emergency_contact_name VARCHAR(100),
        emergency_contact_number VARCHAR(20)
    )");

    // 5. Student Consultations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS student_consultations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        consultation_date DATE NOT NULL,
        vital_signs VARCHAR(100) DEFAULT '',
        chief_complaint TEXT,
        treatment TEXT,
        diagnosis TEXT,
        remarks TEXT,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");

} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>