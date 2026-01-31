---
trigger: always_on
---

# Project Context: Ilmify / Tinytale (Native PHP LMS)

## 1. Database First Policy (CRITICAL)
- **Review Schema First:** Before writing ANY code that interacts with the database, you MUST review the database structure. Do not guess table or column names.
- **Strict SQL:** Ensure all SQL queries use the correct column names found in the `context_schema.txt` or the provided SQL dump.
- **Connection:** Assume the database connection variable `$conn` is already available. Do not create a new connection. Always use: `include '../config/db_connect.php';` (adjust path as needed).

## 2. Tech Stack & Environment
- **Environment:** XAMPP (Localhost).
- **Language:** Native PHP (No frameworks like Laravel/Symfony).
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.
- **Restrictions:** Do not use Composer, npm, or node_modules unless explicitly asked. Code must run on a standard shared hosting environment.

## 3. Design & Responsiveness
- **Mobile-First:** All UI components must be fully responsive. Use Bootstrap grid classes (e.g., `col-12 col-md-6`) to ensure layouts stack correctly on mobile devices.
- **Theme:** "Kindergarten / Playful" style.
  - **Font:** Use 'Nunito' (Google Fonts).
  - **Colors:** Background `#E0F7FA` (Light Sky Blue), Accents: Yellow/Orange/Purple/Red.
  - **Buttons:** Use the `.game-btn` class (Jelly/rounded style) instead of standard Bootstrap buttons.
  - **UI Elements:** Use large rounded corners (`rounded-3` or `rounded-4`) and ample padding.

## 4. Coding Standards
- **Session Management:** Every PHP page must start with `session_start();` followed by a role check (e.g., `if ($_SESSION['role'] !== 'student') header('Location: ../auth/login.php');`).
- **File Structure:** Keep files organized: `student/` for student views, `teacher/` for teacher views, `admin/` for admin views, and `includes/` for shared components like sidebars.