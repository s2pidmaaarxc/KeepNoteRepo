# 📒 KeepNote 
A Google Keep-inspired notes app with Notes, To-Do Lists, Archive, Trash, and Role-Based Access (User, Admin, Super Admin).

---

## 🎨 Features
- ✅ Create, edit, delete Notes and To-Do Lists
- ✅ Check/uncheck to-do items
- ✅ Pin notes to top
- ✅ Color-code notes (9 colors)
- ✅ Archive and restore
- ✅ Trash with permanent delete or restore
- ✅ Search/filter notes
- ✅ Grid and list view toggle
- ✅ Admin dashboard with user management
- ✅ Super Admin audit logs and role management

---

## 📁 File Structure

```
keepnote/
├── index.php        
├── css/      
      └──  index.css
└──  js/
      └── index.js
```

* Not finish, will updated later... 
* Still refactoring things to make it ready, don't worry guys!

---

## ⚙️ Setup Steps

### 1. Requirements
- PHP 5.2.9+ with PDO extension
- MySQL 5.0.51a+ or MariaDB
- A local server like XAMPP, WAMP, or Laragon

### 2. Create the Database
1. Open **phpMyAdmin** (or MySQL CLI)
2. Create a new database named `keepnote_db`
3. Import `database.sql` into it

### 3. How to run on pc/laptop?
1. Put the extracted file on a folder
2. Place the folder on `xampp/htdocs`
3. On your browser type:
   ```
      ex.
         localhost/keepnote
               * keepnote is the name of the folder where I placed the files.
   ```
### To login **Super Admin**:

Super admin is seeded with a hash password `hello world`.  
To change this, you must first create another file `hash.php` to generate a hash in PHP:
```php
echo password_hash('yourNewPassword', PASSWORD_BCRYPT);
//       ex. echo password_hash('superadmin123', PASSWORD_BCRYPT);
//       - if you go to 'localhost/folderWhereYouStoredTheFile/hash.php'

//       then, this is what will it show you:

//       $2y$10$1Gb1qpdm7BaihI8TWgW5AOWh0r9uTdC6wH/uD8cB9eW.sVQwpf.N6
//       - hash of 'superadmin123'
```
After you get your new hash password, update it on your SQL Terminal
```sql
UPDATE users SET password = 'your_bcrypt_hash_here' WHERE username = 'superadmin';
---ex. UPDATE users SET password = '$2y$10$1Gb1qpdm7BaihI8TWgW5AOWh0r9uTdC6wH/uD8cB9eW.sVQwpf.N6'
---    WHERE username = 'superadmin';
```
---
## 👤 Role Capabilities

| Feature                        | User | Admin | Super Admin |
|-------------------------------|------|-------|-------------|
| Create/edit/delete own notes  | ✅   | ✅    | ✅          |
| Archive & trash own items     | ✅   | ✅    | ✅          |
| View other users' notes       | ❌   | ✅    | ✅          |
| Delete other users' notes     | ❌   | ✅    | ✅          |
| Activate/deactivate users     | ❌   | ✅*   | ✅          |
| Change user roles             | ❌   | ❌    | ✅          |
| Delete user accounts          | ❌   | ❌    | ✅          |
| View audit logs               | ❌   | ❌    | ✅          |
| View system stats             | ❌   | ✅    | ✅          |

*Admins can only deactivate regular users, not other admins.

---
