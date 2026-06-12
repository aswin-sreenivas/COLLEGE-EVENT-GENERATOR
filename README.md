# 🎓 CampusConnect – College Event & Student Engagement Platform

A web-based campus management platform developed using PHP and MySQL. CampusConnect enables students to discover events, register for activities, view campus announcements, collect participation certificates, and stay connected with campus life through a centralized system.

---

## 📖 Overview

CampusConnect is designed to improve communication and engagement within educational institutions. The platform allows students to participate in campus events, access announcements, manage profiles, and collect event certificates. Administrators and organizers can manage events, registrations, and campus bulletins through dedicated dashboards.

---

## ✨ Features

### 👨‍🎓 Student Features
- Student Registration
- Secure Login & Logout
- Student Profile Management
- Browse Upcoming Events
- Event Registration
- View Previous Events
- Collect Event Certificates
- Campus Announcements & Bulletins
- Personalized Dashboard

### 🎯 Event Organizer Features
- Organizer Dashboard
- Create and Manage Events
- Track Event Registrations
- Monitor Participant Details
- Event Management Tools

### 👨‍💼 Admin Features
- Admin Login
- User Management
- Event Approval & Management
- Bulletin Management
- Department Management
- Registration Monitoring
- Campus Activity Oversight

---

## 📢 Event Categories

- Technical Events
- Cultural Programs
- Sports Activities
- Workshops
- Seminars

---

## 🛠️ Technologies Used

| Technology | Purpose |
|------------|----------|
| HTML5 | Frontend Structure |
| CSS3 | Styling |
| JavaScript | Client-Side Interactions |
| PHP | Backend Development |
| MySQL | Database Management |
| Apache | Web Server (XAMPP/WAMP) |

---

## 📂 Project Structure

```text
CampusConnect/
│
├── index.php
├── login.php
├── signup.php
├── register.php
├── logout.php
├── Dashboard.php
├── student_profile.php
├── events.php
├── previous_events.php
├── bulletins.php
├── collect_certificate.php
├── organizer.php
├── admin.php
├── about.php
├── functions.php
├── config.php
├── style.css
├── pass.php
│
├── uploads/
│   ├── profiles/
│   └── events/
│
└── campus_connect.sql
```

---

## ⚙️ Installation Guide

### Step 1: Clone Repository

```bash
git clone https://github.com/yourusername/campusconnect.git
```

### Step 2: Move Project Folder

For XAMPP:

```text
C:\xampp\htdocs\
```

For WAMP:

```text
C:\wamp64\www\
```

### Step 3: Create Database

```sql
CREATE DATABASE campus_connect;
```

### Step 4: Import Database

Import:

```text
campus_connect.sql
```

### Step 5: Configure Database Connection

Open:

```php
config.php
```

Update credentials if necessary:

```php
<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "campus_connect";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
```

### Step 6: Run Application

Open browser:

```text
http://localhost/CampusConnect/
```

---

## 🔑 System Modules

### Event Management
- Event Creation
- Event Approval
- Event Registration
- Previous Event Archive
- Event Categories

### Student Portal
- Student Registration
- Login Authentication
- Profile Management
- Dashboard Access
- Certificate Collection

### Bulletin Board
- Campus Announcements
- Notices & Updates
- Event Notifications

### Administration
- User Management
- Event Monitoring
- Department Management
- Bulletin Administration

### Organizer Panel
- Event Publishing
- Registration Tracking
- Participant Monitoring

---

## 💾 Database

Database Name:

```text
campus_connect
```

Possible Main Tables:

```sql
users
events
registrations
bulletins
reviews
certificates
organizers
admins
contact_messages
```

---

## 🚀 Future Enhancements

- QR Code Event Check-In
- Email Notifications
- Certificate PDF Generation
- Event Feedback System
- Mobile Responsive Dashboard
- Attendance Tracking
- Department-Based Event Filtering
- Real-Time Notifications

---

## 🎓 Academic Purpose

This project was developed as part of academic learning and demonstrates:

- PHP Web Development
- MySQL Database Integration
- Authentication & Authorization
- Event Management Systems
- CRUD Operations
- File Upload Handling
- Dynamic Web Applications

---

## 👨‍💻 Developer

### Aswin Sreenivas

Diploma in Computer Engineering

#### Connect

GitHub:
https://github.com/aswin-sreenivas



---

## 📜 License

This project is intended for educational and learning purposes.

---

⭐ If you found this project useful, consider giving it a star on GitHub.
