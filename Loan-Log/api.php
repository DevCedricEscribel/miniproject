<?php
// ============================================================
// Loan Manager API
// Actions (via ?action=...):
//   GET  list_loans
//   GET  get_loan          &id=
//   GET  get_schedule      &loan_id=
//   GET  get_history       &loan_id=
//   GET  calendar_events   &start=&end=   (FullCalendar feed)
//   POST create_loan       {borrower_name, principal, interest_rate, tenure_months, start_date, notes}
//   POST record_payment    {loan_id, schedule_id, payment_date, amount, notes}
//   POST delete_loan       {id}
// ============================================================

require __DIR__ . '/config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

function input() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : $_POST;
}

// ---- EMI / amortization helpers -----------------------------------------
function calc_emi($principal, $annualRatePct, $tenureMonths) {
    $r = ($annualRatePct / 100) / 12;
    if ($r == 0) {
        return round($principal / $tenureMonths, 2);
    }
    $factor = pow(1 + $r, $tenureMonths);
    $emi = $principal * $r * $factor / ($factor - 1);
    return round($emi, 2);
}

function build_schedule($principal, $annualRatePct, $tenureMonths, $startDate, $emi) {
    $r = ($annualRatePct / 100) / 12;
    $balance = $principal;
    $rows = [];
    $date = new DateTime($startDate);

    for ($i = 1; $i <= $tenureMonths; $i++) {
        $date = (clone $date)->modify('+1 month');
        $interest = round($balance * $r, 2);
        $principalComp = round($emi - $interest, 2);

        // Last installment: absorb rounding difference so balance hits exactly 0
        if ($i == $tenureMonths) {
            $principalComp = $balance;
            $emiThis = round($principalComp + $interest, 2);
        } else {
            $emiThis = $emi;
        }

        $balance = round($balance - $principalComp, 2);
        if ($balance < 0) $balance = 0;

        $rows[] = [
            'installment_no'      => $i,
            'due_date'            => $date->format('Y-m-d'),
            'principal_component' => $principalComp,
            'interest_component'  => $interest,
            'emi_amount'          => $emiThis,
            'balance_after'       => $balance,
        ];
    }
    return $rows;
}

try {
    switch ($action) {

        // ---------------------------------------------------------------
        case 'list_loans':
            $stmt = $pdo->query("SELECT * FROM loans ORDER BY created_at DESC");
            echo json_encode($stmt->fetchAll());
            break;

        // ---------------------------------------------------------------
        case 'get_loan':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
            break;

        // ---------------------------------------------------------------
        case 'get_schedule':
            $loanId = (int)($_GET['loan_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM payment_schedule WHERE loan_id = ? ORDER BY installment_no");
            $stmt->execute([$loanId]);
            echo json_encode($stmt->fetchAll());
            break;

        // ---------------------------------------------------------------
        case 'get_history':
            $loanId = (int)($_GET['loan_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM payment_history WHERE loan_id = ? ORDER BY payment_date DESC, id DESC");
            $stmt->execute([$loanId]);
            echo json_encode($stmt->fetchAll());
            break;

        // ---------------------------------------------------------------
        case 'calendar_events':
            // Mark overdue pending items on the fly
            $pdo->exec("UPDATE payment_schedule SET paid_status='overdue'
                        WHERE paid_status='pending' AND due_date < CURDATE()");

            $stmt = $pdo->query("
                SELECT ps.id, ps.due_date, ps.emi_amount, ps.paid_status, ps.installment_no,
                       l.id AS loan_id, l.borrower_name
                FROM payment_schedule ps
                JOIN loans l ON l.id = ps.loan_id
                ORDER BY ps.due_date
            ");
            $rows = $stmt->fetchAll();

            $colors = [
                'pending' => '#0d6efd',
                'paid'    => '#198754',
                'overdue' => '#dc3545',
            ];

            $events = array_map(function ($r) use ($colors) {
                return [
                    'id'    => $r['id'],
                    'title' => $r['borrower_name'] . ' - #' . $r['installment_no'] . ' ($' . number_format($r['emi_amount'], 2) . ')',
                    'start' => $r['due_date'],
                    'color' => $colors[$r['paid_status']] ?? '#6c757d',
                    'extendedProps' => [
                        'loan_id'    => $r['loan_id'],
                        'status'     => $r['paid_status'],
                        'amount'     => $r['emi_amount'],
                        'borrower'   => $r['borrower_name'],
                    ],
                ];
            }, $rows);

            echo json_encode($events);
            break;

        // ---------------------------------------------------------------
        case 'create_loan':
            if ($method !== 'POST') throw new Exception('POST required');
            $d = input();

            $name      = trim($d['borrower_name'] ?? '');
            $principal = floatval($d['principal'] ?? 0);
            $rate      = floatval($d['interest_rate'] ?? 0);
            $tenure    = intval($d['tenure_months'] ?? 0);
            $startDate = $d['start_date'] ?? date('Y-m-d');
            $notes     = trim($d['notes'] ?? '');

            if ($name === '' || $principal <= 0 || $tenure <= 0) {
                throw new Exception('Missing or invalid loan details');
            }

            $emi = calc_emi($principal, $rate, $tenure);
            $schedule = build_schedule($principal, $rate, $tenure, $startDate, $emi);

            $totalPayment  = array_sum(array_column($schedule, 'emi_amount'));
            $totalInterest = round($totalPayment - $principal, 2);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO loans
                (borrower_name, principal, interest_rate, tenure_months, start_date, emi_amount, total_payment, total_interest, outstanding_balance, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $principal, $rate, $tenure, $startDate, $emi, $totalPayment, $totalInterest, $principal, $notes]);
            $loanId = $pdo->lastInsertId();

            $ins = $pdo->prepare("INSERT INTO payment_schedule
                (loan_id, installment_no, due_date, principal_component, interest_component, emi_amount, balance_after)
                VALUES (?,?,?,?,?,?,?)");
            foreach ($schedule as $row) {
                $ins->execute([
                    $loanId, $row['installment_no'], $row['due_date'],
                    $row['principal_component'], $row['interest_component'],
                    $row['emi_amount'], $row['balance_after'],
                ]);
            }

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'loan_id' => $loanId,
                'emi' => $emi,
                'total_payment' => $totalPayment,
                'total_interest' => $totalInterest,
                'schedule' => $schedule,
            ]);
            break;

        // ---------------------------------------------------------------
        case 'record_payment':
            if ($method !== 'POST') throw new Exception('POST required');
            $d = input();

            $loanId     = intval($d['loan_id'] ?? 0);
            $scheduleId = intval($d['schedule_id'] ?? 0);
            $payDate    = $d['payment_date'] ?? date('Y-m-d');
            $amount     = floatval($d['amount'] ?? 0);
            $notes      = trim($d['notes'] ?? '');

            if ($loanId <= 0 || $amount <= 0) throw new Exception('Invalid payment data');

            $pdo->beginTransaction();

            $ins = $pdo->prepare("INSERT INTO payment_history (loan_id, schedule_id, payment_date, amount, notes) VALUES (?,?,?,?,?)");
            $ins->execute([$loanId, $scheduleId ?: null, $payDate, $amount, $notes]);

            if ($scheduleId) {
                $upd = $pdo->prepare("UPDATE payment_schedule SET paid_status='paid', paid_date=?, paid_amount=? WHERE id=?");
                $upd->execute([$payDate, $amount, $scheduleId]);
            }

            // reduce outstanding balance & close loan if fully paid
            $upd2 = $pdo->prepare("UPDATE loans SET outstanding_balance = GREATEST(outstanding_balance - ?, 0) WHERE id = ?");
            $upd2->execute([$amount, $loanId]);

            $bal = $pdo->prepare("SELECT outstanding_balance FROM loans WHERE id = ?");
            $bal->execute([$loanId]);
            $remaining = $bal->fetchColumn();
            if ($remaining <= 0) {
                $pdo->prepare("UPDATE loans SET status='closed' WHERE id=?")->execute([$loanId]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'outstanding_balance' => $remaining]);
            break;

        // ---------------------------------------------------------------
        case 'delete_loan':
            $d = input();
            $id = intval($d['id'] ?? ($_GET['id'] ?? 0));
            $stmt = $pdo->prepare("DELETE FROM loans WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        // ---------------------------------------------------------------
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
