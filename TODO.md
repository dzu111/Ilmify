Ilmify / StudyQuest – TODO
==========================

This file is the **structured version** of `to do.txt`.  
Use it to track what is implemented, in progress, or still pending.

Legend:
- `[ ]` = not started
- `[-]` = in progress / partially done
- `[x]` = completed

Update these checkboxes as you build.

---

## 1. Student Side

- [ ] **Content availability: locked/unlocked when it’s time** (event‑based, not weekly insert)
  - **Initially**: Placeholder content (welcome note, sample video, sample quiz) so dashboard is never blank.
  - **During class**: Teacher pushes note, video, quiz, or **reading session** (PDF/PPT). Students see them live (Focus Mode).
  - **After class ends**: All content used in that class **unlocks** and appears on the student dashboard.
  - **Repeat** per class; **lifetime access** to everything they’ve learned after the last class.
  - Track class sessions (`started_at`, `ended_at`) and **pushed content** per session; unlock when session ends.
- [ ] **Placeholder content**
  - Always show some default content (welcome note, sample video, sample quiz) so new students never see a blank dashboard.
- [ ] **Student dashboard layout**
  - Keep current layout: Notes, Video Gallery, Active Battles (quizzes). Add **Reading sessions** (PDF/PPT) section.
  - Source content: placeholder first; then unlocked content from past classes (after teacher pushed + class ended).
- [ ] **Quiz XP logic**
  - Base XP for first completion.
  - Reduced XP for repeat attempts on the same quiz.
- [ ] **Thumbnail cleanup**
  - Remove / refactor any unused thumbnails or placeholder images related to content.
- [ ] **Fix tracking for quests, notes, and videos**
  - Track:
    - Note viewed state (per student, per content).
    - Video watched state (optionally with progress %).
    - Quest / mission completion.
- [ ] **Focus Mode for online class**
  - Available only when there is an active class for the student.
  - Hide non‑essential UI and show **only** current class content.
  - Auto‑mark attendance when Focus Mode is entered from a valid live‑class link.
- [ ] **Sidebar by subject**
  - Sidebar shows list of subjects.
  - When a subject is selected:
    - Weeks / content / related options filter to that subject.

---

## 2. Parent Side

- [ ] **Teacher info & remarks view**
  - Show child’s teachers (name, subject, basic profile).
  - Show teacher’s academic / behavior remarks and recent alerts.
- [ ] **Progress overview**
  - Simple cards or table for:
    - Attendance summary.
    - Recent quiz scores.
    - Flags (“Needs attention”, “Missing classes”, etc.).

---

## 3. Teacher Side

### 3.1 Curriculum Builder (Preparation Mode)

- [ ] **Subject → Weeks → Content model**
  - Data model for Subject, Week, and Content (notes, videos, quizzes, **reading sessions**).
- [ ] **Master Subject Repository**
  - One subject (e.g. “Math Form 4”) reusable across multiple classes/groups.
  - Weeks and content are created once in the repository.
- [ ] **Content upload & organization UI**
  - Upload PDFs, add YouTube links, create quizzes, **reading sessions** (PDF or PowerPoint), etc. per week.
- [ ] **Content visibility: locked until used in class**
  - Content is hidden from students until teacher pushes it in a live class and that class ends (then it unlocks).

### 3.2 Live Command Center (Execution Mode)

- [ ] **Active / upcoming class list**
  - Show classes from the Admin master schedule (e.g. “Mon 8 PM – Math Group A”).
- [ ] **Start / End class flow**
  - `Start Class` opens live teaching interface and logs live session.
  - `End Class` closes session and stops live pushing.
- [ ] **Push content to screen**
  - For each **note / video / quiz / reading session** (PDF/PPT), add a “Push to Screen” button.
  - Only students in that class see the pushed content.
- [ ] **Real‑time view of connected students**
  - Show list of students currently in the live class.

### 3.3 Class & Attendance Management

- [ ] **Attendance auto‑logger**
  - When student joins a live class, mark them as “Present” for that session.
- [ ] **Manual override**
  - Teacher can mark “Excused” or correct mistaken absent/present.
- [ ] **Simple attendance report per class**
  - Table or export of who attended each session.

### 3.4 Student Progress Tracker (Gradebook)

- [ ] **Gradebook table**
  - Show per‑student:
    - Quiz 1 score, Quiz 2 score, etc.
    - Last login date / last activity.
- [ ] **Alert parents action**
  - Button: “Send Alert to Parent” for weak or inactive students.
  - Use a standard message template (can be improved later).

---

## 4. Admin Side

### 4.1 Dashboard (“Cockpit”)

- [ ] **Business overview widgets**
  - Total revenue this month (from payments table).
  - Active students count vs total capacity.
  - Active classes.
  - Pending issues:
    - Students with subscription expiring soon.
    - Teacher absence flag (e.g. missed classes).

### 4.2 User Management

- [ ] **Teacher management**
  - Create teacher accounts (email/password or username/password).
  - Assign which subjects each teacher can teach.
  - Simple payroll view: number of classes conducted (from live session logs).
- [ ] **Student management**
  - Create student accounts after registration/payment form.
  - Reset passwords on request.
  - Override subscription expiry (extend, freeze, etc.).

### 4.3 Scheduling (Master Schedule)

- [ ] **Calendar view for classes**
  - Create slots by clicking a date/time block.
  - Select subject, teacher, group, and recurrence rules.
- [ ] **Link schedule to dashboards**
  - Teacher dashboard shows upcoming classes.
  - Student dashboard shows their upcoming classes and subjects.

### 4.4 Enrollment Manager

- [ ] **Dual list UI**
  - Left: all active students.
  - Right: students in specific class (e.g. “Math F4 Group A”).
- [ ] **Add / remove students from class**
  - Buttons to move students between lists.

### 4.5 Financials

- [ ] **Transaction history**
  - Store and display all payments (method, amount, date, student).
- [ ] **Online payment hooks**
  - Auto‑log successful payments from Billplz / ToyyibPay / Stripe.
- [ ] **Manual payment entry**
  - Admin can add a payment record for cash/CDM/bank transfer.
- [ ] **Expiry watchlist**
  - List of students whose subscription expires within X days.
  - Action to send standard reminder (e.g. WhatsApp or email).

---

## 5. Cross‑Cutting Features

- [ ] **Role‑based access control**
  - Enforce clear separation between Admin, Teacher, Student, Parent.
- [ ] **Audit logging**
  - Basic logging for key admin/teacher actions (optional but useful later).
- [ ] **UI/UX polish**
  - Consistent design system across `student/`, `teacher/`, `parent/`, and `admin/` panels.

---

## 6. Quick Implementation Notes

- When you implement a feature, **update this file** and mark relevant items as `[x]` or `[-]`.
- Keep `to do.txt` as a raw idea dump; treat `TODO.md` as the **single source of truth** for current progress.

