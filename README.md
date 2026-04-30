# Ticket System – WordPress Plugin

A custom ticket booking plugin for the Toronto Horror Film Festival. It lets attendees register for film screenings directly on the website, sends them a confirmation email, and logs every booking to a Google Sheet.

---

## Table of Contents

- [How It Works](#how-it-works)
- [Plugin Files](#plugin-files)
- [Installation](#installation)
- [Using the Shortcode](#using-the-shortcode)
- [Managing Screenings (Admin Dashboard)](#managing-screenings-admin-dashboard)
- [Google Sheets Integration](#google-sheets-integration)
- [Changing the Confirmation Email](#changing-the-confirmation-email)
- [Quick Reference – Things You Might Need to Change](#quick-reference--things-you-might-need-to-change)
- [Troubleshooting](#troubleshooting)

---

## How It Works

1. A visitor goes to the ticketing page on the website and fills out the booking form.
2. They choose a screening, a ticket type (Reserved or Same Day), and enter their details.
3. On submission:
   - They receive a confirmation email.
   - The booking is logged as a new row in a Google Sheet.
   - If they chose a Reserved ticket, the available seat count for that screening is reduced by the number of tickets they booked.

### Ticket Types

| Type | Cost | How it works |
|---|---|---|
| **Reserved Seat** | $5 e-transfer deposit | Attendee sends a $5 e-transfer and enters the reference number in the form. Their seat is guaranteed. Deposit is refunded at the door. |
| **Same Day Entry (FCFS)** | Free | No deposit required. Seating is first come, first served — not guaranteed. |

---

## Plugin Files

```
ticket-system/
├── ticket-system.php                          # Main plugin file – hooks, admin menu, activation
├── screenings.php                             # Reads screening data from the database
├── form.php                                   # Frontend booking form (shortcode output)
├── handler.php                                # Form submission logic, email sending, Google Sheets logging
└── toronto-horror-film-festival-01544b154a0a.json  # Google API credentials (keep this private)
```

---

## Installation

1. Upload the `ticket-system` folder to `/wp-content/plugins/`.
2. In the WordPress admin, go to **Plugins** and activate **Ticket System**.
3. Make sure the Google Sheets credentials file (`.json`) is present in the plugin folder (see [Google Sheets Integration](#google-sheets-integration)).
4. Make sure your site has outgoing email configured — a plugin like [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) is recommended.

### Requirements

- WordPress (any reasonably recent version)
- PHP with the **`openssl` extension** enabled (required for Google API authentication — most hosts have this on by default)
- Outgoing email configured on the WordPress site

---

## Using the Shortcode

Place the following shortcode on any page or post where you want the booking form to appear:

```
[ticket_form]
```

That's it. The form will render automatically with all active screenings.

---

## Managing Screenings (Admin Dashboard)

Go to **Tools → Manage Screenings** in the WordPress admin.

### Viewing Screenings

The table shows all current screenings with their name, date, location, remaining seats, and status (Available / Sold Out).

### Updating Seat Counts

In the **Seats Remaining** column, change the number and click **Update**. This is useful if you need to manually adjust availability — for example, to account for walk-ins or to re-open a sold-out screening.

> Setting seats to `0` marks a screening as Sold Out. Setting it to any number above `0` makes it Available again.

### Adding a New Screening

Fill in the **Add New Screening** form at the bottom of the page:

| Field | Description |
|---|---|
| **Screening Name** | The full name of the film or event (e.g. `Where Darkness Dwells`) |
| **Short Key** | A unique internal ID used by the plugin (e.g. `wd`, `sf1`). Letters, numbers, and underscores only — no spaces. Once set, **do not change this**. |
| **Date** | Display text shown to attendees (e.g. `Apr 10, 7PM`) |
| **Location** | Venue or room name (e.g. `Hall A`) |
| **Initial Seat Count** | How many reserved seats are available for this screening |

### Removing a Screening

Click **Remove** next to a screening. This permanently deletes it and clears its seat data. Screenings that have already been booked will still exist in the Google Sheet — only the active listing is removed.

---

## Google Sheets Integration

Every booking is automatically logged to a Google Sheet. This section explains how it works and what to do if you need to switch sheets or update credentials.

### What Gets Logged

Each row in the sheet contains:

| Column | Data |
|---|---|
| A | Timestamp |
| B | First Name |
| C | Last Name |
| D | Email |
| E | Screening Name |
| F | Screening Date |
| G | Ticket Type (`reserved` or `fcfs`) |
| H | Number of Tickets |
| I | E-Transfer Reference Number |

### How Authentication Works (Plain Language)

The plugin uses a **Google Service Account** — think of it as a dedicated Google "robot" account that has been granted permission to write to the spreadsheet. The plugin authenticates as this robot using a credentials file (the `.json` file in the plugin folder) and then appends a new row every time someone books a ticket.

You do **not** need to log in to Google or approve anything manually — it all happens automatically in the background.

### The Credentials File

**File:** `toronto-horror-film-festival-01544b154a0a.json`  
**Location:** Inside the plugin folder on the server

This file contains the private key for the service account. **Keep it private** — do not share it or commit it to a public repository.

### Switching to a Different Google Sheet

1. Open the new Google Sheet in your browser.
2. Copy the Sheet ID from the URL. It's the long string of letters and numbers between `/d/` and `/edit` in the URL:
   ```
   https://docs.google.com/spreadsheets/d/THIS_IS_THE_SHEET_ID/edit
   ```
3. Open `handler.php` in a text editor and find this line near the top:
   ```php
   define('STS_SHEET_ID', '15uzyGEO4gvpPwg9alFY_5p06o0McJ6Z1gCcJ3sb8yTI');
   ```
4. Replace the ID inside the quotes with the new Sheet ID and save the file.
5. Make sure the service account has access to the new sheet (see below).

### Granting the Service Account Access to a Sheet

The service account has its own email address, which is inside the credentials `.json` file. To find it:

1. Open `toronto-horror-film-festival-01544b154a0a.json` in a text editor.
2. Look for the `"client_email"` field — it will look something like:
   ```
   festival-bookings@toronto-horror-film-festival.iam.gserviceaccount.com
   ```
3. In Google Sheets, click **Share**, paste that email address, and give it **Editor** access.

Without this step, the plugin will fail silently when trying to log bookings.

### Changing the Sheet Tab Name

By default, the plugin logs to a tab called `Sheet1`. If your sheet uses a different tab name:

1. Open `handler.php`.
2. Find:
   ```php
   define('STS_SHEET_TAB', 'Sheet1');
   ```
3. Replace `Sheet1` with your tab name and save.

### Replacing the Credentials File (New Service Account)

If you create a new Google service account and get a new credentials file:

1. Place the new `.json` file in the plugin folder.
2. Open `handler.php` and find:
   ```php
   define('STS_CREDENTIALS_FILE', 'toronto-horror-film-festival-01544b154a0a.json');
   ```
3. Replace the filename with the new file's name and save.
4. Grant the new service account's email Editor access to the sheet (see above).

---

## Changing the Confirmation Email

The plugin sends confirmation emails using WordPress's built-in `wp_mail()` function. The **sender name and address** are controlled by your WordPress email/SMTP configuration — not by this plugin's code.

To change the from-address or sender name:

- If you use **WP Mail SMTP**: Go to **WP Mail SMTP → Settings** and update the From Email and From Name fields.
- If you use another SMTP plugin: Check its settings page for the same fields.

To change the **content or subject line** of the emails, edit the `sts_handle_ticket_submission()` function in `handler.php`. The reserved ticket email and the FCFS email are handled separately — both are clearly labelled with comments.

---

## Quick Reference – Things You Might Need to Change

| What | Where | How |
|---|---|---|
| Switch to a different Google Sheet | `handler.php` | Update the `STS_SHEET_ID` constant |
| Change the sheet tab name | `handler.php` | Update the `STS_SHEET_TAB` constant |
| Replace the credentials file | `handler.php` | Update the `STS_CREDENTIALS_FILE` constant |
| Add/remove/edit screenings | WordPress Admin | Tools → Manage Screenings |
| Adjust remaining seats | WordPress Admin | Tools → Manage Screenings → Update |
| Change confirmation email sender | SMTP plugin settings | Update From Email / From Name |
| Change email content or subject | `handler.php` | Edit the `$message` and `$subject` variables inside `sts_handle_ticket_submission()` |
| Change the e-transfer email address shown in the form | `form.php` | Find `YOUR EMAIL HERE` in the reserved callout HTML and replace it |

---

## Troubleshooting

### Bookings aren't being logged to Google Sheets

- Check that the credentials `.json` file is present in the plugin folder and the filename matches `STS_CREDENTIALS_FILE` in `handler.php`.
- Check that the service account email has **Editor** access to the Google Sheet.
- Check that the `STS_SHEET_ID` in `handler.php` matches the actual Sheet ID in the URL.
- Check that the `STS_SHEET_TAB` matches the exact name of the tab in the spreadsheet (case-sensitive).
- Check the WordPress error log (`wp-content/debug.log`) — the plugin logs detailed error messages there prefixed with `STS:`.
- Confirm PHP's `openssl` extension is enabled on the server. Without it, the plugin cannot authenticate with Google.

### Confirmation emails aren't being sent

- Confirm that outgoing email is working on the site — send a test email from your SMTP plugin's settings page.
- Check that the attendee's email address was entered correctly.
- Check spam/junk folders.

### Seat counts aren't updating after a booking

- If the site uses a caching plugin, try purging the cache manually.
- The plugin automatically attempts to purge LiteSpeed cache after seat changes. If you're on a different host or caching plugin, you may need to purge manually after adding a screening.

### A screening shows "Sold Out" but seats are available

- Go to **Tools → Manage Screenings**, update the seat count to the correct number, and click **Update**. The sold-out flag will clear automatically once seats are above 0.

### The form isn't showing on the page

- Make sure the `[ticket_form]` shortcode is on the page.
- Make sure the plugin is activated under **Plugins**.
- Make sure there is at least one screening added under **Tools → Manage Screenings** — the form renders nothing if there are no screenings.