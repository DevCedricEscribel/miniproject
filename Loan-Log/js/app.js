// ============================================================
// Loan Calculator & Calendar - Frontend logic
// ============================================================
const API = "api.php";

document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("startDate").value = new Date()
    .toISOString()
    .slice(0, 10);
  document.getElementById("payDate").value = new Date()
    .toISOString()
    .slice(0, 10);

  document.getElementById("previewBtn").addEventListener("click", previewLoan);
  document.getElementById("loanForm").addEventListener("submit", saveLoan);
  document
    .getElementById("refreshLoansBtn")
    .addEventListener("click", loadLoans);
  document
    .getElementById("paymentForm")
    .addEventListener("submit", recordPayment);

  document.getElementById("loansTabBtn").addEventListener("click", loadLoans);
  document.getElementById("calendarTabBtn").addEventListener("click", () => {
    if (!window.__calendarInit) initCalendar();
  });

  loadLoans();
});

// ---- Client-side EMI math (mirrors backend so preview is instant) -------
// FLAT INTEREST MODEL: interest = principal * (rate% / 100), fixed every month.
// Example: principal 2600, rate 2.95% -> interest = 2600 * 0.0295 = 76.70 / month
function calcInterestAmount(principal, ratePct) {
  return +(principal * (ratePct / 100)).toFixed(2);
}

function calcEmi(principal, ratePct, tenureMonths) {
  const interest = calcInterestAmount(principal, ratePct);
  const principalPerMonth = +(principal / tenureMonths).toFixed(2);
  return +(principalPerMonth + interest).toFixed(2);
}

function buildScheduleClient(principal, ratePct, tenureMonths, startDate, emi) {
  const interest = calcInterestAmount(principal, ratePct);
  const principalPerMonth = +(principal / tenureMonths).toFixed(2);
  let balance = principal;
  const rows = [];
  let date = new Date(startDate + "T00:00:00");

  for (let i = 1; i <= tenureMonths; i++) {
    date = new Date(date);
    date.setMonth(date.getMonth() + 1);

    let principalComp = principalPerMonth;
    if (i === tenureMonths) {
      principalComp = balance;
    }

    const emiThis = +(principalComp + interest).toFixed(2);
    balance = +(balance - principalComp).toFixed(2);
    if (balance < 0) balance = 0;

    rows.push({
      installment_no: i,
      due_date: date.toISOString().slice(0, 10),
      principal_component: principalComp,
      interest_component: interest,
      emi_amount: emiThis,
      balance_after: balance,
    });
  }
  return rows;
}

function fmt(n) {
  return (
    "₱" +
    Number(n).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

function getFormValues() {
  return {
    borrower_name: document.getElementById("borrowerName").value.trim(),
    principal: parseFloat(document.getElementById("principal").value),
    interest_rate: parseFloat(document.getElementById("interestRate").value),
    tenure_months: parseInt(document.getElementById("tenureMonths").value),
    start_date: document.getElementById("startDate").value,
    notes: document.getElementById("loanNotes").value.trim(),
  };
}

function previewLoan() {
  const v = getFormValues();
  if (
    !v.borrower_name ||
    !v.principal ||
    !v.tenure_months ||
    !v.start_date ||
    isNaN(v.interest_rate)
  ) {
    alert("Please fill in all required fields.");
    return;
  }
  const emi = calcEmi(v.principal, v.interest_rate, v.tenure_months);
  const schedule = buildScheduleClient(
    v.principal,
    v.interest_rate,
    v.tenure_months,
    v.start_date,
    emi,
  );
  const totalPayment = schedule.reduce((s, r) => s + r.emi_amount, 0);
  const totalInterest = totalPayment - v.principal;

  document.getElementById("summaryCard").style.display = "";
  document.getElementById("sumEmi").textContent = fmt(emi);
  document.getElementById("sumTotalInterest").textContent = fmt(totalInterest);
  document.getElementById("sumTotalPayment").textContent = fmt(totalPayment);

  const body = document.getElementById("scheduleBody");
  body.innerHTML = schedule
    .map(
      (r) => `
    <tr>
      <td>${r.installment_no}</td>
      <td>${r.due_date}</td>
      <td>${fmt(r.principal_component)}</td>
      <td>${fmt(r.interest_component)}</td>
      <td>${fmt(r.emi_amount)}</td>
      <td>${fmt(r.balance_after)}</td>
    </tr>
  `,
    )
    .join("");
}

async function saveLoan(e) {
  e.preventDefault();
  const v = getFormValues();
  if (
    !v.borrower_name ||
    !v.principal ||
    !v.tenure_months ||
    !v.start_date ||
    isNaN(v.interest_rate)
  ) {
    alert("Please fill in all required fields.");
    return;
  }
  try {
    const res = await fetch(`${API}?action=create_loan`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(v),
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    alert("Loan saved successfully!");
    document.getElementById("loanForm").reset();
    document.getElementById("startDate").value = new Date()
      .toISOString()
      .slice(0, 10);
    document.getElementById("summaryCard").style.display = "none";
    document.getElementById("scheduleBody").innerHTML =
      '<tr><td colspan="6" class="text-center text-muted py-4">Fill the form and click Preview</td></tr>';

    document.querySelector('[data-bs-target="#loansTab"]').click();
    loadLoans();
  } catch (err) {
    alert("Error saving loan: " + err.message);
  }
}

// ---- Loans list -----------------------------------------------------------
async function loadLoans() {
  const body = document.getElementById("loansBody");
  body.innerHTML =
    '<tr><td colspan="8" class="text-center text-muted py-4">Loading...</td></tr>';
  try {
    const res = await fetch(`${API}?action=list_loans`);
    const loans = await res.json();
    if (!loans.length) {
      body.innerHTML =
        '<tr><td colspan="8" class="text-center text-muted py-4">No loans yet. Create one in the Calculator tab.</td></tr>';
      return;
    }
    body.innerHTML = loans
      .map(
        (l) => `
      <tr>
        <td>${escapeHtml(l.borrower_name)}</td>
        <td>${fmt(l.principal)}</td>
        <td>${l.interest_rate}%</td>
        <td>${l.tenure_months} mo</td>
        <td>${fmt(l.emi_amount)}</td>
        <td>${fmt(l.outstanding_balance)}</td>
        <td><span class="badge badge-status-${l.status === "active" ? "pending" : "paid"}">${l.status}</span></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary" onclick="openLoan(${l.id}, '${escapeHtml(l.borrower_name)}')"><i class="bi bi-eye"></i></button>
          <button class="btn btn-sm btn-outline-danger" onclick="deleteLoan(${l.id})"><i class="bi bi-trash"></i></button>
        </td>
      </tr>
    `,
      )
      .join("");
  } catch (err) {
    body.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">Failed to load loans: ${err.message}</td></tr>`;
  }
}

function escapeHtml(str) {
  const div = document.createElement("div");
  div.textContent = str;
  return div.innerHTML;
}

async function deleteLoan(id) {
  if (!confirm("Delete this loan and all its records? This cannot be undone."))
    return;
  await fetch(`${API}?action=delete_loan`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id }),
  });
  loadLoans();
  if (window.__calendarInit) window.calendarObj.refetchEvents();
}

// ---- Loan detail modal (schedule + history) --------------------------------
let currentLoanId = null;

async function openLoan(id, name) {
  currentLoanId = id;
  document.getElementById("loanModalTitle").textContent =
    `Loan #${id} — ${name}`;
  document.getElementById("payLoanId").value = id;
  await loadSchedule(id);
  await loadHistory(id);
  new bootstrap.Modal(document.getElementById("loanModal")).show();
}

async function loadSchedule(loanId) {
  const res = await fetch(`${API}?action=get_schedule&loan_id=${loanId}`);
  const rows = await res.json();
  const body = document.getElementById("modalScheduleBody");
  body.innerHTML = rows
    .map(
      (r) => `
    <tr>
      <td>${r.installment_no}</td>
      <td>${r.due_date}</td>
      <td>${fmt(r.emi_amount)}</td>
      <td><span class="badge badge-status-${r.paid_status}">${r.paid_status}</span></td>
      <td>
        ${
          r.paid_status !== "paid"
            ? `<button class="btn btn-sm btn-success" onclick="quickPay(${r.id}, ${loanId}, ${r.emi_amount})">Mark Paid</button>`
            : `<span class="text-muted small">Paid ${r.paid_date}</span>`
        }
      </td>
    </tr>
  `,
    )
    .join("");
}

function quickPay(scheduleId, loanId, amount) {
  document.getElementById("payScheduleId").value = scheduleId;
  document.getElementById("payLoanId").value = loanId;
  document.getElementById("payAmount").value = amount;
  document.querySelector('[data-bs-target="#historySection"]').click();
}

async function loadHistory(loanId) {
  const res = await fetch(`${API}?action=get_history&loan_id=${loanId}`);
  const rows = await res.json();
  const body = document.getElementById("historyBody");
  body.innerHTML = rows.length
    ? rows
        .map(
          (r) => `
        <tr>
          <td>${r.payment_date}</td>
          <td>${fmt(r.amount)}</td>
          <td>${escapeHtml(r.notes || "")}</td>
        </tr>
      `,
        )
        .join("")
    : '<tr><td colspan="3" class="text-center text-muted py-3">No payments recorded yet.</td></tr>';
}

async function recordPayment(e) {
  e.preventDefault();
  const payload = {
    loan_id: document.getElementById("payLoanId").value,
    schedule_id: document.getElementById("payScheduleId").value || null,
    payment_date: document.getElementById("payDate").value,
    amount: document.getElementById("payAmount").value,
    notes: document.getElementById("payNotes").value,
  };
  try {
    const res = await fetch(`${API}?action=record_payment`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.error) throw new Error(data.error);

    document.getElementById("paymentForm").reset();
    document.getElementById("payDate").value = new Date()
      .toISOString()
      .slice(0, 10);
    document.getElementById("payScheduleId").value = "";

    await loadSchedule(currentLoanId);
    await loadHistory(currentLoanId);
    loadLoans();
    if (window.__calendarInit) window.calendarObj.refetchEvents();
  } catch (err) {
    alert("Error recording payment: " + err.message);
  }
}

// ---- Calendar ---------------------------------------------------------------
function initCalendar() {
  const el = document.getElementById("calendar");
  const calendar = new FullCalendar.Calendar(el, {
    initialView: "dayGridMonth",
    height: 650,
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,listMonth",
    },
    events: async (info, success, failure) => {
      try {
        const res = await fetch(`${API}?action=calendar_events`);
        const data = await res.json();
        success(data);
      } catch (err) {
        failure(err);
      }
    },
    eventClick: (info) => {
      const loanId = info.event.extendedProps.loan_id;
      const borrower = info.event.extendedProps.borrower;
      openLoan(loanId, borrower);
    },
  });
  calendar.render();
  window.calendarObj = calendar;
  window.__calendarInit = true;
}
