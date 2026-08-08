# Loan Calculator & Calendar

A self-contained web app for calculating loans, tracking due dates on a calendar, and logging payment history — built with **HTML, Bootstrap 5, vanilla JavaScript, PHP, and MySQL**.

## Features

- **Calculator**: enter principal, annual interest rate, tenure (months), and start date → instantly see the monthly payment (EMI), total interest, total payment, and a full amortization schedule (preview, before saving).
- **Loans list**: every saved loan with principal, rate, tenure, EMI, outstanding balance, and status (active/closed).
- **Calendar**: every due date plotted on a FullCalendar month view, color-coded — blue = pending, green = paid, red = overdue. Click an event to jump straight to that loan.
- **History**: per-loan payment log. Mark a scheduled installment as paid (or record a manual/partial payment) and the outstanding balance updates automatically; the loan auto-closes when fully paid.

## File structure

```
loan-calculator/
├── database.sql     # MySQL schema (run this first)
├── config.php        # DB connection settings — edit this
├── api.php           # Backend API (all loan/schedule/payment logic)
├── index.html         # Main UI page
├── css/style.css
└── js/app.js          # Frontend logic (EMI math, API calls, calendar)
```

## Setup

1. **Create the database.**
   ```bash
   mysql -u root -p < database.sql
   ```
   This creates the `loan_manager` database with three tables: `loans`, `payment_schedule`, and `payment_history`.

2. **Configure the DB connection.**
   Open `config.php` and set your MySQL host/username/password:
   ```php
   $DB_HOST = 'localhost';
   $DB_NAME = 'loan_manager';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```

3. **Serve the app with PHP.**
   From inside the `loan-calculator` folder:
   ```bash
   php -S localhost:8000
   ```
   Then open **http://localhost:8000** in your browser.

   (Any standard PHP host — XAMPP, MAMP, Apache/Nginx + PHP-FPM, etc. — also works. Just make sure the whole folder, including `api.php`, is reachable from the web root.)

## How the numbers are calculated

**Flat interest model.** The interest rate you enter is multiplied straight against the principal to get a fixed monthly interest charge — it does **not** shrink as the balance is paid down:

```
interest_per_month  = principal × (rate% / 100)
principal_per_month = principal / tenure_months
EMI                 = principal_per_month + interest_per_month
```

Example: a loan of 2,600 at 2.95% → interest = 2,600 × 0.0295 = **76.70**, charged every month for the full tenure. Only the principal portion of the payment reduces the outstanding balance each month; the interest portion stays the same amount every time.

Each month, interest is charged on the remaining balance and the rest of the EMI reduces principal — the classic amortization schedule. The last installment is adjusted by a few cents if needed so the balance lands exactly on $0.

## Notes / things you may want to extend

- There's no authentication — add a login layer if this will be exposed beyond your own machine.
- `record_payment` accepts any amount (supports partial/extra payments), not just the exact EMI.
- The calendar re-marks any pending installment past its due date as "overdue" each time it loads.
- All money fields are stored as `DECIMAL(15,2)` for accuracy (no floating-point rounding issues).
