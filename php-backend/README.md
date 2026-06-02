# Goshen Dental Care - PHP & MySQL Production Backend

This folder contains a fully robust, secure, and production-ready backend built with **PHP** and **MySQL**. It directly matches the data structures, types, and operational flow utilized by the React single-page application.

---

## 📁 File Structure

```text
/php-backend
├── database.sql    # Complete MySQL Database Schema and Seed Data
├── db.php          # Secure PDO Connection helper with CORS middleware
├── api.php         # RESTful API Engine (GET/POST routes for all clinical modules)
└── README.md       # Setup & Configuration Quick-Guide
```

---

## 🚀 Setup & Local Deployment Guide

You can easily host this backend using a local development environment (such as **XAMPP**, **WAMP**, **MAMP**, or a **Docker** stack).

### Step 1: Configure MySQL Database
1. Open your MySQL Administrator (such as **phpMyAdmin** or terminal).
2. Create a new database named `goshen_dental`:
   ```sql
   CREATE DATABASE goshen_dental;
   ```
3. Import the `database.sql` file:
   - In **phpMyAdmin**: Select the database, click the **Import** tab, choose `/php-backend/database.sql`, and click **Go**.
   - Via **MySQL Terminal**:
     ```bash
     mysql -u root -p goshen_dental < /php-backend/database.sql
     ```

### Step 2: Configure PHP Connections
1. Open `db.php` in a text editor.
2. Edit the database credentials block to match your local setup:
   ```php
   $db_host = '127.0.0.1';
   $db_name = 'goshen_dental';
   $db_user = 'root';       // Default for XAMPP
   $db_pass = '';           // Default for XAMPP (blank)
   ```

### Step 3: Run the Server
1. Copy the files (`db.php`, `api.php`) into your server's document root (e.g., `/xampp/htdocs/goshen/` or your virtual host directory).
2. Start the Apache / Nginx and MySQL services in your control panel.
3. Your endpoints are now live at:
   - http://localhost/goshen/api.php?resource=services
   - http://localhost/goshen/api.php?resource=appointments
   - http://localhost/goshen/api.php?resource=staff

---

## 🖥️ Connecting your React Client to the PHP API

In the React application, you can seamlessly connect to this PHP backend by updating the communication layer. 

For instance, replace local state or fetch URL endpoints with your live PHP API domain:

```typescript
// Example React fetch action
async function fetchServices() {
  const response = await fetch("http://localhost/goshen/api.php?resource=services");
  const result = await response.json();
  if (result.success) {
    return result.data; // Fully typed Array of DentalService
  }
}
```

## Security Features Included
- **CORS-enabled Headers:** Supports cross-origin queries between your React local development server and Apache.
- **SQL Injection Defended:** Leverages **PDO prepared statements** and bound query parameters exclusively.
- **Atomic SQL Transactions:** Wraps composite actions (such as sub-mitting installments and updating appointment ledger statuses) in atomic transactional rollbacks to ensure database integrity.
