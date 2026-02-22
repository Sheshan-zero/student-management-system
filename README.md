# 🎓 Student Management System (SMS)

A comprehensive web-based Student Management System built with **PHP**, **MySQL**, and **Bootstrap**. The system provides role-based dashboards for **Admins**, **Lecturers**, and **Students** to manage academic operations efficiently.

---

## ✨ Features

### 👨‍💼 Admin
- Manage **Students**, **Lecturers**, and **Users**
- Create and manage **Courses** and **Enrollments**
- Configure **Grade Boundaries** and **Academic Periods**
- Publish **Announcements**
- View **Activity Logs** and generate **Reports**
- Manage **Timetable** schedules

### 👩‍🏫 Lecturer
- View assigned courses and enrolled students
- Record and manage **Attendance** (sessions & records)
- Enter **Marks** and grade **Assignments**
- View class **Timetable**
- Post **Announcements** for students

### 🧑‍🎓 Student
- View enrolled courses and class schedule
- Check **Marks**, **Grades**, and **GPA**
- View **Attendance** records
- Read **Announcements**
- Update personal **Profile**

---

## 🛠️ Tech Stack

| Layer        | Technology                        |
|--------------|-----------------------------------|
| Backend      | PHP 7+ (PDO for database access)  |
| Database     | MySQL / MariaDB                   |
| Frontend     | HTML, CSS, JavaScript, Bootstrap  |
| Server       | Apache (XAMPP / WAMP / LAMP)      |
| Auth         | Session-based with `password_hash` |

---

## 📁 Project Structure

```
Student_Managment_Sys/
├── assets/
│   ├── css/                # Stylesheets
│   └── js/                 # JavaScript files
├── config/
│   └── db.php              # Database connection (PDO)
├── dashboards/
│   ├── admin_dashboard.php
│   ├── lecturer_dashboard.php
│   └── student_dashboard.php
├── database/
│   └── sms_db.sql          # Full schema + seed data
├── includes/
│   ├── auth.php            # Authentication & authorization
│   ├── header.php          # Common page header
│   ├── footer.php          # Common page footer
│   ├── sidebar.php         # Role-based sidebar navigation
│   └── helpers.php         # Utility functions
├── modules/
│   ├── academic_periods/   # Semester/term management
│   ├── activity_log/       # Audit trail
│   ├── announcements/      # Notice board
│   ├── assignments/        # Assignment management
│   ├── attendance/         # Session & record tracking
│   ├── courses/            # Course CRUD
│   ├── enrollments/        # Student-course enrollment
│   ├── lecturers/          # Lecturer management
│   ├── marks/              # Grade entry & management
│   ├── profile/            # User profile
│   ├── reports/            # Academic reports
│   ├── settings/           # System settings
│   ├── students/           # Student management
│   ├── timetable/          # Weekly schedule
│   └── users/              # User administration
├── login.php               # Login page
├── logout.php              # Session logout
├── forgot_password.php     # Password recovery
├── reset_password.php      # Password reset
├── 404.php                 # Not-found page
├── access_denied.php       # Unauthorized access page
├── composer.json           # PHP dependencies
└── README.md
```

---

## 🚀 Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (or any Apache + PHP + MySQL stack)
- PHP 7.0 or higher
- MySQL 5.7+ / MariaDB 10.3+
- [Composer](https://getcomposer.org/) (for PHP dependencies)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/Sheshan-zero/student-management-system.git
   ```

2. **Move to your web server directory**
   ```bash
   # For XAMPP on Windows
   cp -r student-management-system C:/xampp/htdocs/Student_Managment_Sys
   ```

3. **Create the database**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin`)
   - Import the file `database/sms_db.sql`
   - This will create the `sms_db` database with all tables and sample data

4. **Install PHP dependencies**
   ```bash
   cd C:/xampp/htdocs/Student_Managment_Sys
   composer install
   ```

5. **Configure database credentials** (if needed)
   - Edit `config/db.php` and update the credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'sms_db');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

6. **Start XAMPP**
   - Start **Apache** and **MySQL** from the XAMPP Control Panel

7. **Open in browser**
   ```
   http://localhost/Student_Managment_Sys/login.php
   ```

---

## 🔑 Default Login Credentials

| Role     | Email              | Password      |
|----------|--------------------|---------------|
| Admin    | admin@sms.com      | admin123      |
| Lecturer | lecturer@sms.com   | lecturer123   |
| Student  | student@sms.com    | student123    |

---

## 📊 Database Schema

The system uses **16 tables** in a relational MySQL database:

| Table                | Description                          |
|----------------------|--------------------------------------|
| `users`              | Central authentication (all roles)   |
| `students`           | Student-specific data                |
| `lecturers`          | Lecturer-specific data               |
| `courses`            | Course catalog                       |
| `course_assignments` | Lecturer → Course mapping            |
| `enrollments`        | Student → Course enrollment          |
| `marks`              | Individual assessment scores         |
| `final_results`      | Computed totals & grades             |
| `attendance_sessions`| Attendance sessions by lecturers     |
| `attendance_records` | Per-student attendance per session   |
| `timetable`          | Weekly class schedule                |
| `announcements`      | Notices & announcements              |
| `academic_periods`   | Semesters / terms                    |
| `grade_config`       | Admin-configurable grade boundaries  |
| `activity_log`       | Audit trail                          |
| `password_resets`    | Token-based password recovery        |

---

## 🔒 Security Features

- **Password Hashing** — All passwords are hashed with `password_hash()` (bcrypt)
- **Prepared Statements** — PDO prepared statements prevent SQL injection
- **Role-Based Access** — Each role has restricted access to specific modules
- **Session Management** — Secure session-based authentication
- **CSRF Protection** — Token-based form protection

---

## 📝 License

This project is developed for educational purposes in NSBM Green University.

---
