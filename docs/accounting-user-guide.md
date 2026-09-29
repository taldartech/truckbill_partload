# TruckBill Accounting — Simple User Guide

This guide is for branch staff and owners who **do not** need to know Tally or formal accounting.  
It explains everyday words in plain language, then shows **where to click** and **what to do** in TruckBill.

---

## 1. The big idea (in one minute)

Think of your business money like a **notebook**:

| Everyday meaning | Accounting word |
| --- | --- |
| “Who owes us money?” | **Receivable** / Debtor |
| “Whom do we owe?” | **Payable** / Creditor |
| “Cash in the drawer” | **Cash** |
| “Money in bank” | **Bank account** |
| “What we earned from freight” | **Income** |
| “What we spent (cartage, agent, etc.)” | **Expense** |
| “Owner’s money put into the business” | **Capital** |

Every time money moves (or is promised), TruckBill writes **two sides** of the story:

- **Debit (Dr)** — one side of the entry  
- **Credit (Cr)** — the other side  

You do **not** need to memorise debit/credit rules for daily work.  
TruckBill fills most entries automatically when you save invoices, bilties, challans, and inward registers. You mainly:

1. Set up accounts once  
2. Post receipts / payments when cash or bank moves  
3. Open reports to see who owes what  

**Golden rule:** Total Debit must always equal Total Credit. If they don’t match, TruckBill will not save the voucher.

---

## 2. Dictionary of terms (simple meanings)

### Accounts & structure

| Term | Simple meaning | Example in transport |
| --- | --- | --- |
| **Account** | A named “pocket” or “file” for one type of money or person | Cash, HDFC Bank, Patel Trading Co., Freight Income |
| **Chart of Accounts** | The full list of all pockets | Menu → **Chart of Accounts** |
| **Account Group** | A folder that groups similar accounts | Sundry Debtors, Bank Accounts, Direct Expenses |
| **Nature** | What kind of folder it is | Assets, Liabilities, Capital, Income, Expenses |
| **System account** | Built-in account TruckBill creates for you | Cash, Freight Income, Cartage Expense |
| **Selectable** | Can be picked when making a voucher | Turn off for accounts you don’t want staff to pick by mistake |

### Money directions

| Term | Simple meaning |
| --- | --- |
| **Debit (Dr)** | One side of a double entry (shown in reports as Debit column) |
| **Credit (Cr)** | The other side (Credit column) |
| **Balance** | Running total for an account after all entries |
| **Outstanding** | Amount still pending (not fully paid/received) |

### People & money

| Term | Simple meaning |
| --- | --- |
| **Party** | Customer / consignor / consignee you bill |
| **Agent** | Booking / delivery agent you settle with |
| **Consider for accounting** | Tick this on Party/Agent so TruckBill creates their ledger and posts auto entries |
| **Sundry Debtors** | Parties who usually **owe you** (receivables) |
| **Sundry Creditors** | Agents/suppliers you usually **owe** (payables) |
| **Receivables** | List of money **to collect** |
| **Payables** | List of money **to pay** |

### Time

| Term | Simple meaning |
| --- | --- |
| **Financial Year (FY)** | Your books’ year (India: 1 Apr – 31 Mar). **Created automatically** by TruckBill |
| **Current FY** | The year covering today (auto-marked) |
| **Opening Balance** | Starting balances on day 1 of the FY (cash, bank, capital, etc.) |
| **As on date** | “Balances calculated up to this date” |

### Vouchers (transactions)

| Term | Simple meaning | When you use it |
| --- | --- | --- |
| **Voucher** | One numbered accounting entry (like a slip) | Any money movement in books |
| **Receipt** | Money **came in** (cash/bank) | Party paid you |
| **Payment** | Money **went out** (cash/bank) | You paid an agent |
| **Journal** | Adjustment between accounts (not always cash) | Agent settlements, inward crossing |
| **Sales** | Income / billing entry | Usually from **Invoice** (auto) |
| **Purchase** | Expense / purchase-side entry | e.g. cartage due on bilty (auto) |
| **Contra** | Transfer between cash and bank | Cash deposited in bank |
| **Opening** | Starting balances voucher | Once per FY (or when starting books) |
| **Debit / Credit Note** | Correction / return style entries | Advanced — use when accountant advises |
| **Posted** | Saved and included in reports | Normal status |
| **Cancel** | Mark voucher cancelled (kept for history, excluded from reports) | Mistake you don’t want in reports |
| **Reverse** | Create opposite voucher to undo effect | Correct a posted entry cleanly |

### Reports

| Report | What it answers |
| --- | --- |
| **Account Ledger** | Full story of **one** account (party, cash, bank, etc.) |
| **Day Book** | What happened **day by day** (all vouchers) |
| **Cash Book** | Only **Cash** movements |
| **Bank Book** | Only a selected **bank** account |
| **Trial Balance** | All accounts’ totals — check that books are balanced |
| **Profit & Loss** | Income vs expenses for a period (profit or loss) |
| **Balance Sheet** | What you **own** vs what you **owe** + capital on a date |
| **Receivables** | Who still owes **you** |
| **Payables** | Whom **you** still owe |

---

## 3. Menu map (where things live)

### Under Transactions / main menu

- **Accounting Vouchers** — list & create vouchers; cancel / reverse  
- **Bank Accounts** — your real bank A/cs (also creates ledger accounts)

### Under ACCOUNTING

- **Chart of Accounts** — all ledgers  
- **Account Groups** — folders / natures  
- **Opening Balances** — enter starting balances  

Financial years are managed automatically in the background (no menu screen).

### Under REPORTS

- Account Ledger, Day Book, Cash Book, Bank Book  
- Trial Balance, Profit & Loss, Balance Sheet  
- Receivables, Payables  

### Under MASTER DATA (linked to accounts)

- **Parties** — enable **Consider for accounting** for customers you bill  
- **Agents** — enable **Consider for accounting** for agents you settle  

---

## 4. First-time setup (do this once per company)

Follow this order. Skip steps only if your admin already finished them.

### Step A — Financial Year (automatic)

No screen to open. TruckBill creates the Indian FY (1 Apr – 31 Mar) when accounting is used, marks the year covering today as current/open, and closes past years by date.

### Step B — Chart of Accounts (mostly automatic)

1. Open **Chart of Accounts**.  
2. TruckBill already creates system accounts (Cash, Freight Income, expenses, etc.).  
3. Add extra accounts only if you need them (e.g. “Petrol Expense”).  
4. Put each new account in the right **Account Group**.

### Step C — Bank Accounts

1. Open **Bank Accounts** → **Create**.  
2. Enter bank name, account number, IFSC.  
3. TruckBill links a ledger under **Bank Accounts** group for you.

### Step D — Opening Balances

1. Open **Opening Balances**.  
2. Set **As on date** (usually FY start).  
3. Enter lines, for example:  
   - Debit **Cash** ₹2,50,000  
   - Debit **HDFC Current A/c** ₹7,50,000  
   - Credit **Owner’s Capital** ₹10,00,000  
4. Wait until status shows **Balanced**, then **Post Opening Voucher**.

### Step E — Parties & Agents for accounting

1. Open **Parties** (or **Agents**).  
2. Edit the ones you want in books.  
3. Turn **ON** **Consider for accounting**.  
4. Save — TruckBill creates their ledger under Debtors/Creditors.

Without this tick, invoices / agent settlements for that party/agent **will not** post to accounts.

---

## 5. Day-to-day use

### A. Work you already do (auto accounting)

When **Consider for accounting** is on, TruckBill can post vouchers automatically for:

| You save this… | Accounting effect (typical) |
| --- | --- |
| **TBB Invoice** to a party | Party **owes** you (Debit party), **Freight Income** (Credit) |
| **Bilty** with **Cartage = Due** + cartage account | Cartage expense + payable to that account |
| **Inward Register** with agent freight | Agent crossing expense + agent payable |
| **Challan** with **Pay by agent** | Agent settlement journal |

You usually **do not** recreate these by hand. Check them in **Accounting Vouchers** or **Day Book**.

### B. When party pays you (Receipt)

1. Go to **Accounting Vouchers → Create**.  
2. Type = **Receipt**.  
3. Date = payment date.  
4. Lines (example: party paid ₹10,000 in cash):  
   - Debit **Cash** 10,000  
   - Credit **Party’s account** 10,000  
5. Ensure **Balanced** → **Post Voucher**.

If paid into bank, Debit the **bank account** instead of Cash.

### C. When you pay an agent (Payment)

1. **Create** voucher, type = **Payment**.  
2. Example: paid ₹5,000 from bank to agent:  
   - Debit **Agent’s account** 5,000  
   - Credit **Bank** 5,000  
3. Post when balanced.

### D. Cash deposited in bank (Contra)

1. Type = **Contra**.  
2. Example:  
   - Debit **Bank** 20,000  
   - Credit **Cash** 20,000  

### E. Fixing mistakes

| Situation | What to do |
| --- | --- |
| Just posted wrong voucher | Open voucher → **Reverse** (creates opposite entry) or **Cancel** |
| Need small correction | Prefer **Reverse**, then post a correct new voucher |
| Don’t delete history | Cancel/Reverse keeps an audit trail |

---

## 6. How to read common screens

### Accounting Vouchers list

- **Date** — business date of the entry  
- **Voucher No.** — auto number (REC-000001, PAY-000001, JV-…)  
- **Type** — Receipt / Payment / Journal / Sales…  
- **Narration** — short note (e.g. invoice no., challan no.)  
- **Status** — posted / cancelled  
- Click voucher number to see Debit/Credit lines  

### Account Ledger

1. Select an account (party, cash, bank…).  
2. Choose start & end date → **Get Ledger**.  
3. Read top to bottom: each row is a voucher; **Balance** updates as you go.  
4. Click voucher no. to open full entry.

### Receivables / Payables

- **Receivables** — focus on debtors (collect money).  
- **Payables** — focus on creditors/agents (pay money).  
- Use these for daily follow-up calls.

### Trial Balance

- Quick health check: total Debit ≈ total Credit.  
- If something looks wrong, open that account’s ledger.

### Profit & Loss

- Income section vs Expenses section for the period.  
- Net figure = profit (positive) or loss (negative).

### Balance Sheet

- **Assets** (cash, bank, what parties owe you…)  
- **Liabilities & Capital** (what you owe + owner’s capital + profit)  
- Pick **As on** date and refresh.

---

## 7. Mini examples (transport)

### Example 1 — Bill a party

1. Party “Patel Trading” has **Consider for accounting** ON.  
2. You create Invoice INV/0001 for ₹11,800 (with GST).  
3. TruckBill posts a **Sales** voucher: Debit Patel, Credit Freight Income.  
4. Receivables show Patel outstanding.

### Example 2 — Party pays half in cash

1. Create **Receipt** ₹5,900.  
2. Debit Cash / Credit Patel.  
3. Patel’s ledger shows less outstanding.

### Example 3 — Cartage due on bilty

1. On bilty, Cartage Type = **Due**, amount ₹500, cartage account = agent/creditor.  
2. TruckBill posts **Purchase**-style entry: Debit Cartage Expense, Credit that account.  
3. Payables / agent ledger increases.

### Example 4 — Pay agent from bank

1. **Payment** voucher: Debit agent, Credit bank.  
2. Agent outstanding reduces; bank book shows outflow.

---

## 8. Do’s and Don’ts

**Do**

- Enable accounting only for parties/agents you actually track in books.  
- Use the same bank ledger that matches the real bank A/c.  
- Check **Day Book** at end of day.  
- Keep narration clear (invoice no., challan no., party name).  
- Keep Debit = Credit before posting.

**Don’t**

- Don’t post the same invoice twice by hand if it already auto-posted.  
- Don’t edit old history by inventing random journals without understanding — reverse first.  
- Don’t close the financial year until your accountant confirms.  
- Don’t delete accounts that already have voucher history (TruckBill blocks unsafe deletes).

---

## 9. Who should do what

| Role | Typical tasks |
| --- | --- |
| **Branch user** | Receipts/payments, view ledgers & day book, follow receivables |
| **Admin / accountant** | Chart of accounts, opening balances, cancel/reverse, P&L / Balance Sheet |
| **Owner** | Receivables/Payables summary, Profit & Loss, Balance Sheet |

---

## 10. Quick checklist — “Is accounting working?”

1. Accounting is in use (FY is created automatically in the background).  
2. Chart of Accounts shows Cash + system incomes/expenses.  
3. At least one **Bank Account** created.  
4. Opening balances posted (if you started mid-year or at FY start).  
5. Sample party has **Consider for accounting** ON.  
6. Create a test invoice → see a **Sales** voucher in Accounting Vouchers.  
7. Open **Account Ledger** for that party → invoice line appears.  
8. Post a small **Receipt** → outstanding reduces.

---

## 11. Need help from an accountant?

Share these screens with them:

- Chart of Accounts  
- Trial Balance  
- Profit & Loss  
- Balance Sheet  
- Party / Agent ledgers  

They speak “Tally language”; TruckBill’s reports match those ideas even if button names differ slightly.

---

## 12. Glossary cheat-sheet (print this page)

- **Dr** = Debit column  
- **Cr** = Credit column  
- **FY** = Financial Year  
- **CoA** = Chart of Accounts  
- **OB** = Opening Balance  
- **JV** = Journal Voucher  
- **REC / PAY** = Receipt / Payment voucher numbers  

---

*Document version: for TruckBill Partload accounting module. Written for non-accountants.*
