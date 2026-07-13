# Application Feature User Guide

This document explains how to use each feature of the application from an end-user perspective.

## Table of Contents

1. [Landing Page](#1-landing-page)
2. [Creating a New Account](#2-creating-a-new-account)
3. [Logging In](#3-logging-in)
4. [Email Verification](#4-email-verification)
5. [Forgot Password](#5-forgot-password)
6. [Dashboard](#6-dashboard)
7. [Edit Profile](#7-edit-profile)
8. [Change Password](#8-change-password)
9. [Logging Out](#9-logging-out)

---

## 1. Landing Page

The landing page is the first entry point when you open the application.

### For Visitors (Not Logged In)

**Available actions:**

- **Log in** button — leads to the login page
- **Get Started** button — leads to the registration page

### For Logged-In Users

If you are already authenticated:

- The navbar shows **Dashboard** and **Logout** buttons
- Click **Go to Dashboard** to go to the main dashboard page

---

## 2. Creating a New Account

### Accessing the Registration Page

Visit `/register` or click **Get Started** from the landing page.

### Filling Out the Registration Form

| Field | Requirements | Example |
|---|---|---|
| **Full Name** | Required | John Doe |
| **Email Address** | Valid email, not already registered | john@example.com |
| **Password** | Min. 8 chars, uppercase, lowercase, number, symbol | `Secret!Pass123#` |
| **Confirm Password** | Must match Password | `Secret!Pass123#` |

### Password Validation

As you type, the system validates in real-time:

- Minimum 8 characters long
- Contains uppercase (A–Z) and lowercase (a–z) letters
- Contains at least one number (0–9)
- Contains at least one symbol (!@#$%^&*)
- Password confirmation matches

### Common Validation Errors

| Error | Message | Solution |
|---|---|---|
| Email already taken | "The email has already been taken." | Use a different email or log in |
| Password too short | "Password must be at least 8 characters long." | Use at least 8 characters |
| Weak password | Various messages per missing criterion | Follow the checklist above |
| Confirmation mismatch | "Password must be the same as the confirmation" | Ensure both fields match |

### After Registration

1. Account is created
2. You are redirected to the email verification page
3. A verification email is sent to your registered address

---

## 3. Logging In

### Accessing the Login Page

Visit `/login` or click **Log in** from the landing page.

### Login Form

1. Enter your **Email Address**
2. Enter your **Password**
3. Click **Login**

### Login Outcomes

| Scenario | Result |
|---|---|
| Wrong credentials | Error: "These credentials do not match our records." |
| Correct credentials, email verified | Redirect to `/dashboard` |
| Correct credentials, email not verified | Redirect to `/email/verify` |

---

## 4. Email Verification

Email verification is required after registration and whenever you log in with an unverified account.

### Verification Page (`/email/verify`)

**Message displayed:** *"Verify your email address"*

### How to Verify

**Option 1 — Open the email link:**

1. Open the verification email sent to your inbox
2. Click the verification link
3. Return to the application — you will be redirected to the dashboard

**Option 2 — Resend the email:**

1. Click **Resend Verification Email** on the verification page
2. A new email will be sent
3. A success notice will confirm: *"A new verification link has been sent"*

### Notes

- If you log out before verifying, your email remains unverified
- You will be asked to verify again on every subsequent login until you do so

---

## 5. Forgot Password

### Requesting a Reset Link

1. From the login page, click **Forgot Password?** or visit `/forgot-password`
2. Enter your registered **email address**
3. Click **Send Instructions**
4. A password reset link will be sent to your inbox

### Reset Password Page

After clicking the link in the email, you are taken to `/reset-password`:

| Field | Requirements |
|---|---|
| **Email** | Pre-filled from the link |
| **New Password** | Same policy as registration |
| **Confirm New Password** | Must match New Password |

Click **Reset Password** to apply the change. On success you are redirected to the login page.

### Notes

- Reset links expire after a few hours
- If the link has expired, restart the process via **Forgot Password?**

---

## 6. Dashboard

Accessible at `/dashboard` — requires authentication and email verification.

### Stats Cards

Three summary cards are shown at a glance:

| Card | What it shows |
|---|---|
| **Total Users** | Number of registered users with growth trend |
| **Revenue** | Current revenue figure |
| **System Status** | Health status of all services |

### Recent Activity Table

A table showing the latest user activity log with:

- User name
- Activity description
- Date
- Status (Success / Failed)

---

## 7. Edit Profile

Accessible at `/edit-profile` from the sidebar or dashboard.

### Profile Photo

- Click the avatar (or **Change Photo**) to upload a new profile picture
- Accepted formats: JPG, GIF, PNG — max 5 MB; square images work best
- A preview is shown immediately before saving
- Click **Remove** to revert to the default initial avatar

### Updating Name & Email

| Field | Behavior |
|---|---|
| **Full Name** | Updated immediately on save |
| **Email Address** | Triggers a verification step (see below) |

### Email Change Flow

Changing your email address does **not** apply immediately:

1. Enter the new email and click **Save Changes**
2. A warning banner appears: *"A verification email has been sent to [new email]. Please check your inbox and click the link to confirm the email change."*
3. The email field still shows the **current** email with a **Pending Verification** badge
4. Once you click the link in the verification email, the new address is applied
5. Until confirmed, the old email remains active

### Save Button

While the form is submitting, the button shows a spinner and the label changes to **Saving…** to prevent duplicate submissions.

---

## 8. Change Password

Accessible at `/change-password` from the **Edit Profile** page or sidebar.

### Form Fields

| Field | Notes |
|---|---|
| **Current Password** | Required to authorize the change |
| **New Password** | Same strength requirements as registration |
| **Confirm Password** | Must match New Password |

All password fields have a **show/hide toggle** (eye icon).

### Password Strength Indicator

As you type in **New Password**, a colored bar appears below the field:

| Color | Strength |
|---|---|
| Red | Weak |
| Amber | Medium |
| Green | Strong |

### Security Tips (shown on the page)

- Use a unique password not used on other accounts
- Include uppercase, lowercase, numbers, and symbols
- Avoid personal information (name, birthdate, etc.)
- Never share your password with anyone

### Submit Behavior

The **Update Password** button shows a spinning hourglass icon and the label changes to **Updating…** while the request is processing. The button is disabled to prevent duplicate submissions.

### Success / Error Feedback

- On success: a success alert is shown at the top of the page
- On error (e.g., wrong current password): an error alert is shown with the relevant message

---

## 9. Logging Out

Click **Logout** from the navbar (top-right corner) or sidebar.

- The session is terminated immediately
- You are redirected to the login page
- Protected routes (`/dashboard`, `/edit-profile`, etc.) are inaccessible until you log in again

---

## User Journey Summary

```
Landing Page
│
├─→ Not registered? → Register (/register)
│                         ↓
│                   Email Verification (/email/verify)
│                         ↓
│                     Dashboard (/dashboard)
│
└─→ Already have account? → Login (/login)
                                ↓
                          Email verified?
                          ├─→ Yes → Dashboard
                          └─→ No  → Email Verification

From Dashboard:
├─→ Edit Profile (/edit-profile)
│       └─→ Change Password (/change-password)
└─→ Logout

Forgot password? → /forgot-password → email link → /reset-password → Login
```
