# ACME Employee Management System

A web-based HR portal that centralizes the employee lifecycle, from recruitment to resignation, with role-based access control (RBAC).

**Screenshots and project details:** [soriato.github.io/portfolio/project/employee-management-system](https://soriato.github.io/portfolio/project/employee-management-system)

## Portals

| Portal | What it does |
| --- | --- |
| **HR** | Recruitment pipeline and onboarding, employee records, promotions, leave and resignation requests, violations, Certificate of Employment (COE) requests, settings |
| **Manager** | Team overview, performance evaluations, leave and resignation approvals, position changes, requests for new employees |
| **Employee** | Profile, leave requests, performance, reports, resignation, viewing and downloading their COE |
| **Candidate** | Sign up, browse job openings, apply with document upload, track and cancel applications |

## Features

- Four role-based portals: HR, Manager, Employee, and Candidate
- Recruitment pipeline and onboarding tracking
- Leave management and performance evaluations
- Automated Certificate of Employment (COE) generation
- Candidate document uploads to Google Drive
- Email notifications through SendGrid
- Password reset with an emailed verification code

## Tech Stack

- **Backend:** PHP (vanilla)
- **Frontend:** HTML, CSS, JavaScript (vanilla), [Bootstrap 5](https://getbootstrap.com/)
- **Database:** MySQL / MariaDB (XAMPP)
- **APIs:** [SendGrid](https://sendgrid.com/) (email), [Google Drive API](https://developers.google.com/drive) (file storage)

## Running Locally

1. Install [XAMPP](https://www.apachefriends.org/) and [Composer](https://getcomposer.org/).
2. Clone this repository into `xampp/htdocs` as a folder named `empMan`:
   ```bash
   git clone https://github.com/soriaToV2/employee-management-system.git empMan
   ```
3. Install the PHP dependencies (there are three `composer.json` files):
   ```bash
   cd empMan
   composer install
   cd candidatePage && composer install && cd ..
   cd hrPage/sendgrid && composer install && cd ../..
   ```
4. Start Apache and MySQL in XAMPP, then create a database named `ems_db` in phpMyAdmin and import the database schema.
   Database credentials are set in `connection.php`.
5. Add your API credentials (these files are not in the repository):
   - Copy `hrPage/sendgrid/config.example.php` to `hrPage/sendgrid/config.php` and add your SendGrid API key.
   - Copy `candidatePage/google_config.example.php` to `candidatePage/google_config.php` and add your Google OAuth client ID and secret (used for candidate sign-in with Google).
   - Put your Google Cloud service account key at `candidatePage/service-account-key.json`.
6. Open `http://localhost/empMan` in your browser. (The folder name matters: the Google sign-in redirect is set to `/empMan/` in `google_config.php`.)

## Author

**Marc Angelo Soria** · [Portfolio](https://soriato.github.io/portfolio/) · [GitHub](https://github.com/soriaToV2)
