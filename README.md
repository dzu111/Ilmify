StudyQuest / Tinytale – Gamified Online Tuition Platform

StudyQuest (code name: Tinytale) is a hybrid Learning Management System (LMS) for tuition centers.  
It combines **gamification** (XP, levels, avatars) with **live teaching tools** (real‑time content pushing, attendance tracking)
so one system can handle day‑to‑day teaching, scheduling, and payments.

### Project Overview
- **Type**: Web application (PHP + MySQL)
- **Target use**: 100+ students, multiple teachers, one admin/owner
- **Key ideas**:
  - **Focus Mode** – distraction‑free learning view for active classes
  - **Live Command Center** – teacher pushes notes/videos/quizzes/**reading sessions** (PDF/PPT) to student screens in real time
  - **Content availability: locked → unlocked when it’s time** – event‑based on class end, not weekly insert (see below)
  - **XP System** – quizzes and actions give XP; repeated quizzes give reduced XP

### Tech Stack
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: Native PHP (no heavy frameworks)
- **Database**: MySQL (relational)
- **Hosting target**: Shared hosting / cPanel style (e.g. XAMPP locally, Hostinger/etc in production)
- **External services (planned/possible)**:
  - Payments: Billplz / ToyyibPay / Stripe
  - Live classes: Zoom (links managed from Admin dashboard)

### Directory Overview (current codebase)
- `index.php` – public landing page (`StudyQuest` marketing page + login button).
- `auth/` – login/register/check/logout flow.
- `student/` – student dashboard and learning experience (XP, quests, quizzes, focus mode, etc.).
- `parent/` – parent portal (progress + alerts from teachers/admin).
- `teacher/` – teacher dashboard (curriculum builder, live command center, reports).
- `admin/` – owner cockpit (business metrics, classes, users, payments).
- `config/` – database / app configuration (environment‑specific).
- `assets/` – shared CSS, JS, images, icons.
- `to do.txt` – original raw brainstorm notes (kept as reference).
- `TODO.md` – structured, up‑to‑date task list (for you + the AI helper).

> If any folders above are missing in your local clone, they are simply not created/committed yet.

### Roles and Responsibilities

#### 🎓 Student
- **Dashboard & content flow** (see **Content availability** below):
  - **Initially**: Placeholder content (welcome note, sample video, sample quiz) so the dashboard is never blank.
  - **During class**: Teacher pushes note, video, quiz, or **reading session** (PDF / PowerPoint). Students see them live (e.g. Focus Mode).
  - **After class ends**: All content used in that class **unlocks** and appears on the student dashboard.
  - **Repeat** for each class; after the last class, **lifetime access** to everything they’ve learned.
- **Content types**: Notes, Videos, Quizzes, **Reading sessions** (PDF or PowerPoint).
- **XP system**:  
  - Normal quiz = full XP.  
  - **Repeat quiz = reduced XP** (to prevent farming).
- **Focus Mode (live class mode)**:
  - Only shows **current class content** while class is live.
  - Auto‑attendance when joining a live session.
- **Sidebar behaviour**:
  - Sidebar groups content by **subject** (subject dropdown).
  - Other menu options follow the current selected subject.

#### 👨‍👩‍👧 Parent
- View **teacher info** and **remarks** about the child.
- Receive **alerts** (e.g. from teacher Gradebook) when performance is low or attendance is poor.

#### 👨‍🏫 Teacher
- **Curriculum Builder (Preparation Mode)**:
  - Structure: **Subject → Weeks → Content**.
  - Content types: **Notes** (PDF), **Videos** (YouTube), **Quizzes**, **Reading sessions** (PDF or PowerPoint).
  - Content is hidden from students until used in class (see **Content availability** below).
  - Backed by a **Master Subject Repository** (e.g. “Math Form 4” shared across classes).
- **Live Command Center (Execution Mode)**:
  - Shows list of **active / scheduled classes** (e.g. “Monday 8 PM Group A”).  
  - Clicking **Start Class** opens the live teaching view.
  - Each **note / video / quiz / reading session** has a **“Push to Screen”** button – all students in that class see it instantly.
- **Class & Attendance Management**:
  - Real‑time **attendance list**: who is present / absent.
  - Auto‑logger: joining the live class marks student as **Present**.
  - Teacher can manually mark **Excused** or adjust attendance.
- **Student Progress Tracker (Gradebook)**:
  - See students in a table: Quiz scores, last login, etc.
  - Trigger **“Send Alert to Parent”** for weak or inactive students (templated message).
- Teacher **does not** handle billing, schedule creation, or global user accounts.

#### 🛡️ Admin (Owner)
- **Business cockpit dashboard**:
  - Total revenue this month.
  - Active students vs capacity.
  - Active classes.
  - Important alerts (students expiring soon, teacher absence, etc.).
- **User management**:
  - Create teacher & student accounts.
  - Assign which subjects a teacher can handle.
  - Reset passwords.
  - Adjust subscription / expiry dates (e.g. add grace days).
- **Scheduling (Master Schedule)**:
  - Calendar view to create slots:  
    - Select **Subject**, **Teacher**, **Day/Time**, **Recurrence**.
  - This automatically populates teacher & student dashboards with upcoming classes.
- **Enrollment manager**:
  - Manage which students belong to each class/group.
  - UI concept: dual list – **All active students** vs **Students in “Math F4 Group A”**.
- **Financials**:
  - See transaction history.
  - Auto‑log successful online payments (Billplz/Stripe/etc).
  - Add manual payments for cash/bank transfer.
  - See **expiry watchlist** (subscriptions expiring soon) and send reminders (e.g. WhatsApp templates).

### Content availability: locked vs unlocked when it’s time

**Use locked/unlocked when it’s time (event‑based), not weekly insert.**

- **Weekly insert**: Content unlocks on a fixed schedule (e.g. “Week 3 unlocks Jan 15”). Unlock is **calendar‑based** and doesn’t depend on whether the class happened or the teacher used that content.
- **Locked/unlocked when it’s time**: Content is **locked** until (1) the teacher **pushes** it during a live class, and (2) that **class ends**. Then it **unlocks** for enrolled students and stays on their dashboard **forever** (lifetime access). Unlock is **event‑based** (class end).

**Why locked/unlocked is better here:**  
It matches the flow: teacher pushes → class ends → content available. You can still organise by “Week 1”, “Class 1”, etc. in the UI; **availability** is gated by “used in a finished class,” not by calendar week.

**Implementation sketch:**  
Track **class sessions** (e.g. `started_at`, `ended_at`) and **pushed content** per session. When `ended_at` is set, all content pushed in that session becomes **unlocked** for students in that class. Placeholder content (welcome note, sample video, sample quiz) is always visible so the dashboard is never blank.

---

### Admin vs Teacher – Responsibility Matrix

| Feature             | Admin (Owner)                                          | Teacher                                      |
| ------------------- | ------------------------------------------------------ | -------------------------------------------- |
| Schedule class      | Creates master schedule slots                          | Read‑only; sees “Next Class …”               |
| Assign students     | Enrolls students into groups/classes                   | View‑only list of their own students         |
| Curriculum          | Can view / audit                                       | Creates and maintains subject content        |
| Live control        | Can monitor (for QA / audit)                           | Runs live classes and pushes content         |
| Payments & expiry   | Full control (add, edit, override)                     | No access                                    |

### High‑Level Flow (Day‑to‑Day)

- **New student joins**  
  - Parent contacts center → Admin creates student account → Enrolls in class(es) → Logs payment.
- **Teacher prepares**  
  - Uses **Curriculum Builder** to create/organize content (weeks, quizzes, notes, videos).
- **Class time**  
  - Teacher opens **Live Command Center**, presses **Start Class**, pushes content, and attendance is logged automatically.
- **After class / weekly**  
  - Teacher reviews **Gradebook** and sends alerts to parents if needed.
  - Admin checks dashboard + payments and watches for expiring students.

### Development Notes

- Local dev assumption: XAMPP with PHP + MySQL.
- Make sure `config/` contains your local DB connection settings.
- When adding new features, also update:
  - `README.md` – for high‑level concepts and flows.
  - `TODO.md` – for concrete implementation tasks and their status.

For a detailed, implementation‑level task breakdown, see `TODO.md`.