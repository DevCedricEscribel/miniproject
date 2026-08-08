-- ============================================================
-- Loan Calculator & Calendar - Database Schema
-- ============================================================
CREATE DATABASE IF NOT EXISTS loan_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loan_manager;

-- Main loan records
CREATE TABLE IF NOT EXISTS loans (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    borrower_name        VARCHAR(120)   NOT NULL,
    principal            DECIMAL(15,2)  NOT NULL,
    interest_rate        DECIMAL(6,3)   NOT NULL,           -- interest rate PER MONTH, %
    tenure_months        INT            NOT NULL,
    start_date           DATE           NOT NULL,
    emi_amount           DECIMAL(15,2)  NOT NULL,
    total_payment        DECIMAL(15,2)  NOT NULL,
    total_interest       DECIMAL(15,2)  NOT NULL,
    outstanding_balance  DECIMAL(15,2)  NOT NULL,
    status               ENUM('active','closed') DEFAULT 'active',
    notes                VARCHAR(255)   NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Amortization schedule (one row per installment / due date)
CREATE TABLE IF NOT EXISTS payment_schedule (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    loan_id              INT            NOT NULL,
    installment_no       INT            NOT NULL,
    due_date             DATE           NOT NULL,
    principal_component  DECIMAL(15,2)  NOT NULL,
    interest_component   DECIMAL(15,2)  NOT NULL,
    emi_amount           DECIMAL(15,2)  NOT NULL,
    balance_after         DECIMAL(15,2) NOT NULL,
    paid_status          ENUM('pending','paid','overdue') DEFAULT 'pending',
    paid_date            DATE           NULL,
    paid_amount          DECIMAL(15,2)  NULL,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    INDEX idx_due_date (due_date),
    INDEX idx_loan (loan_id)
) ENGINE=InnoDB;

-- Payment history log (actual payments received, can be partial / extra)
CREATE TABLE IF NOT EXISTS payment_history (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    loan_id       INT           NOT NULL,
    schedule_id   INT           NULL,
    payment_date  DATE          NOT NULL,
    amount        DECIMAL(15,2) NOT NULL,
    notes         VARCHAR(255)  NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES payment_schedule(id) ON DELETE SET NULL
) ENGINE=InnoDB;
