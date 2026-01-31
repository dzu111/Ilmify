# Pending Tasks & Gap Analysis

Based on the project structure and current code state, here is a detailed list of features that are **Pending**, **Partial/Simulated**, or **Missing**.

## 1. Student Portal
- [ ] **Focus Mode (Live Class)**
  - *Current Status*: Missing / Concept only.
  - *Requirement*: A dedicated "Live Class" view that hides other UI elements and shows only the pushed content (PDF/Video) in real-time.
- [ ] **Content Unlocking Logic**
  - *Current Status*: Likely manual or incomplete.
  - *Requirement*: System to keep content locked until a class session starts/ends, then unlock it for lifetime access.
- [ ] **Reading Sessions Interaction**
  - *Current Status*: File exists (`reading_sessions.php`), but deep integration with the "Push to Screen" feature from the teacher side is likely missing.

## 2. Teacher Portal
- [ ] **Parent Alerts (Functionality)**
  - *Current Status*: **Simulated**. `teacher/students.php` uses `alert()` JS and does not actually send emails or save to the database.
  - *Requirement*: Implement `parent_alerts` database table and email sending logic (PHPMailer or similar).
- [ ] **Live Command Center (Push Logic)**
  - *Current Status*: UI Validation needed.
  - *Requirement*: Ensure the "Push" buttons in `live_class.php` actually update a state that the Student Dashboard watches (AJAX/Polling) to update their view immediately.

## 3. Admin Portal
- [ ] **Payment Automation (Hooks)**
  - *Current Status*: UI likely exists (`payments.php`), but backend hooks for Billplz/ToyyibPay/Stripe are missing.
  - *Requirement*: `webhook.php` to handle callbacks and auto-approve transactions.
- [ ] **Subscription Expiry Watchlist**
  - *Current Status*: Pending.
  - *Requirement*: Automated cron job or admin view highlight for students expiring in < 3 days.

## 4. General / Infrastructure
- [ ] **Database & Security**
  - *Current Status*: Basic `session_start()` checks.
  - *Requirement*: Ensure strict Role-Based Access Control (RBAC) on **every** processing file (not just views).
- [ ] **Responsive Design Polish**
  - *Current Status*: Bootstrap used, but "Kindergarten" theme needs consistent "Game Btn" styling across all portals (some standard buttons still exist).

---

**Immediate Recommendation:**
Start by implementing the **Parent Alert** real functionality or the **Student Focus Mode**, as these are high-impact user-facing features.
