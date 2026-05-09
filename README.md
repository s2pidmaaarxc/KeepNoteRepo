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
* I'll upload the updated files, later or tomorrow 🙂‍↕️

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
