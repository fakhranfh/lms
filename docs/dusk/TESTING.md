# Feature Testing Documentation (Laravel Dusk)

This document summarizes all features tested end-to-end using **Laravel Dusk** on
this boilerplate. Each feature is accompanied with a list of test scenarios and
relevant screenshots.

## Table of Contents

1. [Landing Page](#1-landing-page)
2. [Registration](#2-registration)
3. [Login](#3-login)
4. [Email Verification](#4-email-verification)
5. [Forgot & Reset Password](#5-forgot--reset-password)
6. [Logout & Page Protection](#6-logout--page-protection)
7. [Edit Profile](#7-edit-profile)
8. [Password Change Alert](#8-password-change-alert)
9. [Running Tests](#running-tests)

---

## Test Files Summary

| File | Feature | Scenarios |
| --- | --- | --- |
| `tests/Browser/LandingPageTest.php` | Landing Page | 8 |
| `tests/Browser/Auth/RegistrationTest.php` | Registration | 12 |
| `tests/Browser/Auth/LoginTest.php` | Login | 6 |
| `tests/Browser/Auth/EmailVerificationTest.php` | Email Verification | 4 |
| `tests/Browser/Auth/PasswordResetTest.php` | Forgot & Reset Password | 6 |
| `tests/Browser/Auth/LogoutTest.php` | Logout & Page Protection | 3 |
| `tests/Browser/EditProfileTest.php` | Edit Profile | 5 |
| `tests/Browser/PasswordChangeAlertTest.php` | Password Change Alert | 1 |

---

## 1. Landing Page

File: `tests/Browser/LandingPageTest.php`

The main public page displaying a hero section, application name in navbar, and
different navigation links for guests and authenticated users.

![Landing Page](images/landing-page.png)

### Test Scenarios

| Scenario | Expected |
| --- | --- |
| `landing page can be rendered` | Displays "Solid Foundation for Your App" heading. |
| `landing page displays correct heading and description` | Hero heading and description fully displayed. |
| `guest users see login and register links` | Guests see **Log in** and **Register** links. |
| `guest users can navigate to login from landing page` | Clicking **Log in** leads to `/login`. |
| `guest users can navigate to register from landing page` | Clicking **Get Started** leads to `/register`. |
| `authenticated users see dashboard link` | Logged-in users see **Dashboard**, not **Log in**. |
| `authenticated users can navigate to dashboard from landing page` | Clicking **Go to Dashboard** leads to `/dashboard`. |
| `landing page has app name in navbar` | Application name (`config('app.name')`) shown in navbar. |

---

## 2. Registration

File: `tests/Browser/Auth/RegistrationTest.php`

New account registration with **server-side** and **client-side** password strength
validation (JavaScript). On success, user is redirected to the email verification page.

![Registration Page](images/register-page.png)

Example display when password doesn't meet requirements or email is already taken:

![Registration Error](images/register-error.png)

### Test Scenarios

| Scenario | Expected |
| --- | --- |
| `registration page can be rendered` | Form displays Full Name, Email, Password, Confirm Password. |
| `user can register with valid data` | Account saved, redirected to `/email/verify`. |
| `registration fails when name is empty` | Message "The name field is required." |
| `registration fails when email is empty` | Message "The email field is required." |
| `registration fails when email format is invalid` | Message "The email field must be a valid email address." |
| `registration fails when email is already taken` | Message "The email has already been taken." |
| `registration fails when password is less than 8 characters` | JS error: minimum 8 characters. |
| `registration shows js error when password has no mixed case` | JS error: must contain uppercase & lowercase. |
| `registration shows js error when password has no numbers` | JS error: must contain a number. |
| `registration shows js error when password has no symbols` | JS error: must contain a symbol. |
| `registration shows js error when password confirmation does not match` | JS error: password confirmation must match. |
| `registration page has login link` | "Login here" link leads to `/login`. |

> Note: Tests mock the `api.pwnedpasswords.com` API so password leak validation
> doesn't call the actual external service.

---

## 3. Login

File: `tests/Browser/Auth/LoginTest.php`

Authentication of registered users. Successful login redirects to `/dashboard`,
while incorrect credentials display an error message.

![Login Page](images/login-page.png)

Display when credentials are wrong:

![Login Error](images/login-error.png)

### Test Scenarios

| Scenario | Expected |
| --- | --- |
| `login page can be rendered` | Form displays Email, Password, Login, Forgot Password. |
| `user can login with valid credentials` | Redirected to `/dashboard`. |
| `login fails with wrong password` | Message "These credentials do not match our records." |
| `login fails with unregistered email` | Message "These credentials do not match our records." |
| `login page has link to register page` | **Register** link leads to `/register`. |
| `login page has link to forgot password` | **Forgot Password?** link leads to `/forgot-password`. |

---

## 4. Email Verification

File: `tests/Browser/Auth/EmailVerificationTest.php`

Users who haven't verified their email are redirected to a verification notice page
after login. Users can resend the verification link or logout.

![Email Verification Notice](images/email-verify-notice.png)

### Test Scenarios

| Scenario | Expected |
| --- | --- |
| `unverified user sees email verification notice after login` | Redirected to `/email/verify`. |
| `verified user goes to dashboard after login` | Redirected to `/dashboard`. |
| `resend verification email button shows success message` | Message "A new verification link has been sent". |
| `logout button on verify page works` | **Log out** button redirects to `/login`. |

---

## 5. Forgot & Reset Password

File: `tests/Browser/Auth/PasswordResetTest.php`

Password recovery flow: request a reset link via email, then set a new password
using a valid token. Email notifications are faked during testing.

![Forgot Password Page](images/forgot-password-page.png)

New password form (accessed via token):

![Reset Password Page](images/reset-password-page.png)

### Test Scenarios

| Scenario | Expected |
| --- | --- |
| `forgot password page can be rendered` | Displays form and "Send Instructions" button. |
| `reset link can be requested with valid email` | Message "Link has been sent to your email address". |
| `reset link fails with unregistered email` | Message "We can't find a user with that email address". |
| `reset password page loads with valid token` | New Password and Confirm New Password fields are displayed. |
| `reset password shows js error with weak password` | JS error: minimum 8 characters. |
| `forgot password page has back to login link` | "Back to log in" link leads to `/login`. |

---

## 6. Logout & Page Protection

File: `tests/Browser/Auth/LogoutTest.php`

Ensures users can log out and that protected pages (`/dashboard`) cannot be accessed
without authentication.

![Dashboard](images/dashboard.png)

### Test Scenarios

| Scenario | Expected |
| --- | --- |
| `authenticated user sees logout button and can logout` | **Logout** button redirects to `/login`. |
| `after logout user cannot access protected pages` | After logout, the login page (`Email Address`) is shown. |
| `guest browsing dashboard is redirected to login` | Guest visiting `/dashboard` is redirected to `/login`. |

---

## 7. Edit Profile

File: `tests/Browser/EditProfileTest.php`

Allows authenticated users to update their name, email address, and profile photo.
Changing the email triggers a pending verification flow before the new address is applied.

![Edit Profile Page](images/edit-profile-page.png)

After successfully saving changes:

![Edit Profile Success](images/edit-profile-success.png)

### Test Scenarios

| Scenario | Expected |
| --- | --- |
| `edit profile page can be rendered` | Edit Profile page is accessible for authenticated users. |
| `user can update name successfully` | Name is updated and message "Profile updated successfully." is shown. |
| `update profile fails when name is empty` | Message "Full name is required". |
| `changing email triggers pending verification notice` | Pending email address is shown on the page. |
| `update profile fails when email format is invalid` | Message "Please enter a valid email address". |

---

## 8. Password Change Alert

File: `tests/Browser/PasswordChangeAlertTest.php`

Verifies that a success alert is displayed after a user successfully changes their
password from the `/change-password` page.

![Change Password Page](images/change-password-page.png)

After successfully updating the password:

![Change Password Success](images/change-password-success.png)

### Test Scenarios

| Scenario | Expected |
| --- | --- |
| `password change displays success alert` | After submitting valid current and new passwords, the message "Your password has been changed successfully" is displayed. |

---

## Running Tests

Prerequisites:

- Frontend assets are built: `npm run build`
- Database is available and migrated (Dusk uses the `DatabaseMigrations` trait)
- Google Chrome is installed (ChromeDriver is provided by the Dusk package)

Run all Dusk tests:

```bash
php artisan dusk
```

Run a specific test file:

```bash
php artisan dusk tests/Browser/Auth/LoginTest.php
```

Run by test name:

```bash
php artisan dusk --filter="user can login with valid credentials"
```

> **Updating documentation screenshots**: screenshots in `docs/dusk/images/` are
> captured by running the dedicated script below. Re-run it whenever the UI changes
> to keep the documentation accurate.

```bash
php artisan dusk tests/Browser/CaptureDocScreenshotsTest.php
```
