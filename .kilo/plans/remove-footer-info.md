# Plan: Remove Footer Information

## Goal
Remove the specific footer elements from the Easy!Appointments backend that display:
- Easy!Appointments
- Easy!Appointments v1.6.0
- Alex Tselegidis
- Alex Tselegidis © 2026
- Software Development
- Bajo licencia de GPL-3.0

## Target File
- `application/views/components/backend_footer.php`

## Proposed Changes
1. Remove the `<div>` block containing the Easy!Appointments logo, name, and version (lines 9-15).
2. Remove the `<div>` block containing the Alex Tselegidis logo, name, copyright year, and "Software Development" text (lines 17-23).
3. Remove the `<div>` block containing the "Licensed under" text and GPL-3.0 link (lines 25-30).

## Remaining Elements
The following elements will remain in the backend footer:
- Language selector dropdown
- "Go to booking page" link
- User display name greeting ("Hello, [Name]!")

## Additional Changes
- Remove the "Powered By Easy!Appointments" branding from the frontend booking footer (`application/views/components/booking_footer.php`), while retaining the legal notice, imprint, language selector, and login/backend link.
