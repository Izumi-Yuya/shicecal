# Shise-Cal Project Overview

## Purpose
Shise-Cal (シセカル) is a comprehensive facility information management web application designed for Japanese organizations. It provides centralized facility data management with role-based access control and approval workflows.

## Tech Stack
- **Backend**: Laravel 9.x, PHP 8.2+, MySQL 8.0/SQLite
- **Frontend**: Blade Templates, Bootstrap 5.1.3, Vanilla JavaScript (ES6+), Vite
- **Key Dependencies**: barryvdh/laravel-dompdf, elibyy/tcpdf-laravel, spatie/laravel-activitylog, laravel/sanctum

## Key Features
- Facility management with basic info and land information
- Role-based access control (Admin, Editor, Approver, Viewer)
- Approval workflows for data changes
- File management for facility documents
- Export capabilities (PDF reports, CSV data)
- Comment system with notifications
- Maintenance history tracking
- Activity logging and audit trails