# AcademyFlow — Manual Smoke Test Checklist

Run this against the deployed instance before calling it live. It's a golden-path-plus-key-edge-cases pass through every module, not a replacement for the automated test suite (`php artisan test`) — that already covers the exhaustive cases. This is about confirming the *deployed* environment actually behaves the way the tests say it should: real web server, real database, real file storage, real email/session config.

Reset expectation: you'll need one Super Admin login and you'll create one fresh institute along the way. A 403 in this app shows a custom "Access Denied" card with a specific message and a link back to the dashboard/login — if you ever see a raw Laravel/PHP error page instead, that's worth flagging regardless of what step you're on.

## 1. Login & Access Control

- [ ] Super Admin can log in
- [ ] Wrong password shows a clear error and does not log in
- [ ] After repeated failed attempts, further attempts are rate-limited (a "too many attempts, try again in N seconds" message)
- [ ] Logout works and returns to the login page

## 2. Institute Creation (Super Admin)

- [ ] Create a new institute — pick an institute type, enable a few modules, set an admin name/email/password
- [ ] The new institute appears in the Institutes list
- [ ] Its admin can log in with the credentials just set
- [ ] It appears in the Subscriptions list on a 7-day trial
- [ ] Logged in as that institute's admin, the dashboard shows the "N days left in your free trial" banner, and it's dismissible
- [ ] Block the institute (toggle status) — confirm its admin can no longer log in (clear message, not a generic error) — then unblock and confirm login works again

## 3. Roles & Staff (Institute Admin)

- [ ] Create a new role with a deliberately limited set of permissions (e.g., Students view+add only, nothing else)
- [ ] Create a staff member on that role
- [ ] Log in as that staff member — confirm they see only what their permissions allow in the sidebar, and get the "Access Denied" page for anything else (try a direct URL to something they shouldn't have)
- [ ] Deactivate the staff member — confirm they can no longer log in
- [ ] Reactivate — confirm login works again

## 4. Students

- [ ] Admit a new student, including a photo upload
- [ ] Student appears in the list with a correctly auto-generated student code
- [ ] Search, and each filter (status, course, batch), narrow the list correctly
- [ ] Edit a student's details — changes save and show immediately
- [ ] Change a student's status (active/inactive/dropped)
- [ ] Delete (soft-delete) a student — disappears from the default list, appears in Trash, is not permanently gone

## 5. Courses, Teachers, Batches

- [ ] Create a course, a teacher, and a batch (batch assigned to that course and teacher)
- [ ] Try deleting a course or batch that has active students assigned — confirm a specific, readable error naming what's blocking it, not a crash or a silent no-op
- [ ] Deactivate a course — confirm it no longer appears as a choice when admitting a *new* student, while a student already on it still shows it correctly on their own profile

## 6. Fees

- [ ] Open a student's fee record — total/net fee reflects their assigned course's fee
- [ ] Collect a partial payment — status flips to "Partial", pending amount updates correctly
- [ ] Collect the remaining balance — status flips to "Paid"
- [ ] Apply a discount with a reason — amounts recalculate correctly
- [ ] Void a payment — it's struck through/badged as voided rather than disappearing, and no longer counts toward the paid amount
- [ ] Download a receipt PDF for a real payment — opens cleanly and shows the correct amount and institute details

## 7. Attendance

- [ ] Mark today's attendance for a batch of students
- [ ] As a role with only add (not edit) permission, confirm correcting a *past* date is blocked, while marking *today* works
- [ ] View the monthly grid for a student or batch — present/absent/late/leave counts and the percentage look right
- [ ] Export attendance to CSV — opens correctly in a spreadsheet application

## 8. Reports

- [ ] Student Report — filter by course, batch, status, and admission date range; each narrows results correctly
- [ ] Attendance Report — both Summary and Detailed modes work; requesting a Detailed range over 31 days shows the friendly "please narrow your range" message instead of attempting a huge table
- [ ] Fee Report — filter by status, confirms results narrow correctly
- [ ] Collection Report — filter by payment mode and staff member; the total-collected figure at the bottom matches what's actually shown above it
- [ ] Export both CSV and PDF for at least one report — both download and open cleanly
- [ ] A staff member without reports access gets "Access Denied" on every report page, not just a hidden nav link

## 9. Subscriptions (Super Admin)

- [ ] Create a Plan, edit it, then deactivate it — confirm the deactivated plan no longer appears as a choice when activating a *new* subscription
- [ ] Create a Coupon with a discount and a max-use count
- [ ] Activate a subscription on a plan while applying that coupon — the discounted amount is correct and the coupon's use count increments by exactly one
- [ ] Cancel a subscription — confirm that institute's users can no longer log in, with a clear subscription-specific message (not a generic error)
- [ ] Reactivate it — confirm login is restored
- [ ] The Super Admin dashboard shows correct Total Institutes / Active Subscriptions / Trial / Expired+Cancelled counts, and a plausible Monthly Recurring Revenue figure

## Before calling it live

- [ ] `APP_DEBUG=false` in the production `.env` (visiting a broken URL should never show a stack trace)
- [ ] A database backup has actually run and produced a real, non-empty archive file (check the storage location, don't just trust the schedule)
- [ ] The server's crontab has the scheduler entry registered, and `subscriptions:expire` / the daily backup show a recent successful run in the logs
