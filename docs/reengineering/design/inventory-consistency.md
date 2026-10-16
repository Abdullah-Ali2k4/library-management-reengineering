# Book Inventory Consistency Model

**Project:** Library Management System Reengineering  
**Repository:** `Abdullah-Ali2k4/library-management-reengineering`  
**GitHub Issue:** [#8 — Define Book Inventory Consistency Model](https://github.com/Abdullah-Ali2k4/library-management-reengineering/issues/8) (Project Issue #6)  
**Date:** 2026-10-16  
**Status:** Draft — Proposal for Team Review  
**Auditor / Technical Lead:** Senior Software Reengineering Engineer  
**Related Artifacts:**
- [`docs/reengineering/baseline/legacy-baseline.md`](../baseline/legacy-baseline.md)
- [`docs/reengineering/baseline/regression-test-plan.md`](../baseline/regression-test-plan.md)
- [`docs/reverse-engineering/requirements/recovered-srs.md`](../../reverse-engineering/requirements/recovered-srs.md)
- [`docs/reverse-engineering/requirements/business-rules.md`](../../reverse-engineering/requirements/business-rules.md)
- [`docs/reverse-engineering/design/erd/erd.md`](../../reverse-engineering/design/erd/erd.md)

---

## 1. Executive Summary and Problem Statement

### 1.1 Legacy Inventory Inconsistencies (Conflict C-05, C-08, DEF-INV-01)
During the reverse-engineering and baseline analysis, several fundamental weaknesses in the inventory management model were identified:
1. **Four Independent Writers with No Central Authority:**
   In the legacy codebase, `books.available_copies` is modified independently across 4 disconnected scripts:
   - [`books/add.php:57`](file:///home/abdullah/Desktop/Library-management-system/books/add.php#L57): Initializes `available_copies = total_copies`.
   - [`books/edit.php:24, 61`](file:///home/abdullah/Desktop/Library-management-system/books/edit.php#L24): Overwrites `available_copies` directly from raw `$_POST['available_copies']`.
   - [`issue_return/issue.php:54`](file:///home/abdullah/Desktop/Library-management-system/issue_return/issue.php#L54): Decrements `available_copies = available_copies - 1`.
   - [`issue_return/return.php:51`](file:///home/abdullah/Desktop/Library-management-system/issue_return/return.php#L51): Increments `available_copies = available_copies + 1`.
2. **Arbitrary Manual Tampering in Book Edit:**
   In [`books/edit.php:36-39`](file:///home/abdullah/Desktop/Library-management-system/books/edit.php#L36-L39), the system validates only that `available_copies >= 0` and `available_copies <= total_copies`. It **completely ignores active loans**. An operator can manually change `available_copies` from 0 to 5 while all 5 copies are physically checked out to members, causing immediate inventory corruption.
3. **Copy-Count Reduction Conflict (Conflict C-08):**
   An editor can reduce `total_copies` below the number of copies currently on loan (e.g. lowering total from 5 to 1 when 3 copies are on loan), creating an mathematically unsatisfiable inventory state.
4. **Lack of Database Invariant Constraints (Schema Smell SC-1):**
   The legacy `books` table schema contains no `CHECK` constraints to prevent `available_copies < 0` or `available_copies > total_copies` at the storage level.

### 1.2 Objective
This document defines an authoritative, mathematically sound **Inventory Consistency Model** for the reengineered Library Management System. It specifies the single source of truth, mathematical invariants, operational transition rules, concurrency controls, and schema migration requirements.

---

## 2. Core Inventory Model and Invariants

### 2.1 Entity Definitions
In the library catalog, each record in `books` represents a title title holding physical copy units:
- **`total_copies` ($T \in \mathbb{Z}_{\ge 1}$):** The total number of physical copies owned by the library for a given book catalog record.
- **`available_copies` ($A \in \mathbb{Z}_{\ge 0}$):** The number of physical copies currently present on the shelf and available for immediate borrowing.
- **`active_loans` ($L \in \mathbb{Z}_{\ge 0}$):** The number of physical copies currently checked out to library members. Defined as:
  $$L = \text{COUNT}(\text{issued\_books WHERE book\_id} = B \text{ AND status} = \text{'issued'})$$

### 2.2 Mathematical Consistency Invariant
At all times and across all transactional operations, the database state must satisfy the **Fundamental Inventory Invariant**:

$$\boxed{T = A + L}$$

From this equality, the following boundary constraints must strictly hold:
1. **Non-Negativity Constraint:**
   $$A \ge 0 \quad \text{and} \quad L \ge 0$$
2. **Upper Bound Constraint:**
   $$A \le T \quad \text{and} \quad L \le T$$
3. **Minimum Total Copies Constraint:**
   $$T \ge L$$
   *(The library can never possess fewer total copies than the number of copies currently checked out to patrons).*

---

## 3. Single Source of Truth: Architectural Decision

During requirements and design assessment, two implementation architectures were evaluated:

| Criterion | Option A: Derived Dynamically from Loans | Option B: Synchronized Counter with Service Gate (Recommended) |
| :--- | :--- | :--- |
| **Mechanics** | Remove `available_copies` column from `books`. Compute $A = T - \text{COUNT}(\text{active loans})$ via SQL view or join on every read. | Retain `available_copies` as a persisted column on `books`, but channel all updates through an authoritative service with row locks and DB `CHECK` constraints. |
| **Data Integrity** | Absolute mathematical guarantee against counter drift. | Guaranteed by atomic transactions, pessimistic locking, and database constraints. |
| **Query Performance** | Requires `LEFT JOIN` and `GROUP BY` on `issued_books` for every catalog view, search, and dashboard count. | $O(1)$ fast indexed reads on `books.available_copies`. |
| **Implementation Complexity** | High: requires rewriting query logic in `books/view.php`, `search.php`, `index.php`, and circulation views. | Low-Medium: preserves existing read views while centralizing write mutations into a single service. |
| **Suitability for Project** | High refactoring risk under short deadlines. | **Optimal for student reengineering:** achieves strict consistency while preserving working view code. |

### Architectural Decision:
The reengineered system adopts **Option B (Synchronized Counter with Authoritative Service Gate)**:
1. `available_copies` remains a stored, indexed column on `books`.
2. **Direct manual editing of `available_copies` is permanently removed** from `books/edit.php`.
3. An authoritative **Circulation & Inventory Domain Service** encapsulates all state modifications.
4. An automated reconciliation utility audits and corrects counter synchronization.

---

## 4. Operation-by-Operation Transition Rules

```mermaid
stateDiagram-v2
    [*] --> Created: Book Creation (add.php)
    note right of Created: T >= 1, A = T, L = 0

    Created --> Stocked: Catalog Active
    Stocked --> CheckedOut: Issue Book (issue.php)
    note right of CheckedOut: Precondition: A > 0\nAtomic: A = A - 1, L = L + 1

    CheckedOut --> Stocked: Return Book (return.php)
    note right of Stocked: Precondition: Loan active\nAtomic: A = A + 1, L = L - 1

    Stocked --> Stocked: Edit Total Copies (edit.php)
    note right of Stocked: Precondition: New T >= L\nUpdate: A = A + (New T - Old T)
```

### 4.1 Book Creation (`books/add.php`)
- **Actor:** Staff or Administrator.
- **Preconditions:** Title provided, author selected, ISBN valid.
- **Rule:** `total_copies` must be an integer $\ge 1$.
- **State Transition:**
  $$T_{\text{new}} = \text{submitted } T \quad (\ge 1)$$
  $$A_{\text{new}} = T_{\text{new}}$$
  $$L = 0$$
- **Enforcement:** The application form initializes `available_copies = total_copies` automatically. The form does not display a separate input for available copies.

### 4.2 Book Editing (`books/edit.php`)
- **Actor:** Staff or Administrator.
- **Preconditions:** Book exists.
- **Rules:**
  1. **Manual available-copies override is forbidden:** The input field `name="available_copies"` is removed from the HTML edit form.
  2. **Active loan boundary validation:** Before committing changes to `total_copies`, the system computes active loans:
     $$L = \text{COUNT}(\text{issued\_books WHERE book\_id} = B \text{ AND status} = \text{'issued'})$$
  3. **Rejection Condition:** If the submitted $T_{\text{new}} < L$, the edit is rejected:
     > *"Validation Error: Total copies cannot be reduced to $T_{\text{new}}$. There are currently $L$ active loan(s) checked out for this book."*
  4. **Available Copies Adjustment:** If $T_{\text{new}} \ge L$, available copies is adjusted by the delta in total copies:
     $$\Delta T = T_{\text{new}} - T_{\text{old}}$$
     $$A_{\text{new}} = A_{\text{old}} + \Delta T$$
     *(Example: If $T=5, A=2, L=3$, increasing $T$ to 7 results in $\Delta T = +2$, so $A_{\text{new}} = 4$. If reducing $T$ to 3, $\Delta T = -2$, so $A_{\text{new}} = 0$. $T=A+L$ is preserved).*

### 4.3 Book Borrowing / Issue (`issue_return/issue.php`)
- **Actor:** Staff or Administrator.
- **Preconditions:** Valid member selected, due date $\ge$ issue date.
- **Transaction Sequence with Pessimistic Locking:**
  1. `START TRANSACTION;`
  2. Select and lock catalog row:
     ```sql
     SELECT available_copies, total_copies FROM books WHERE book_id = ? FOR UPDATE;
     ```
  3. **Invariant Guard:** Verify $A > 0$. If $A \le 0$, execute `ROLLBACK;` and display error:
     > *"This book is currently out of stock ($0$ available copies)."*
  4. Create circulation record:
     ```sql
     INSERT INTO issued_books (book_id, member_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, 'issued');
     ```
  5. Decrement inventory:
     ```sql
     UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?;
     ```
  6. `COMMIT;`

### 4.4 Book Return (`issue_return/return.php`)
- **Actor:** Staff or Administrator.
- **Preconditions:** Active loan record exists.
- **Transaction Sequence with Pessimistic Locking:**
  1. `START TRANSACTION;`
  2. Select and lock loan record:
     ```sql
     SELECT book_id FROM issued_books WHERE issue_id = ? AND status = 'issued' FOR UPDATE;
     ```
  3. If no active row matches (already returned or invalid ID), execute `ROLLBACK;` and display error:
     > *"This borrowing record has already been marked as returned."*
  4. Update loan status:
     ```sql
     UPDATE issued_books SET status = 'returned', return_date = CURDATE() WHERE issue_id = ?;
     ```
  5. Select and lock book record:
     ```sql
     SELECT available_copies, total_copies FROM books WHERE book_id = ? FOR UPDATE;
     ```
  6. **Invariant Guard:** Verify $A + 1 \le T$. If $A + 1 > T$, log integrity warning and cap at $T$.
  7. Increment inventory:
     ```sql
     UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?;
     ```
  8. `COMMIT;`

---

## 5. Concurrency Control and Race Condition Prevention

### 5.1 Race Condition Scenario in Unsynchronized Systems
Without strict isolation, concurrent checkouts can lead to negative availability (overselling):

```
Thread 1 (Borrower X)                 Thread 2 (Borrower Y)
---------------------                 ---------------------
Reads Book A: avail = 1               Reads Book A: avail = 1
Validates: 1 > 0 (OK)                 Validates: 1 > 0 (OK)
Inserts issued_books                  Inserts issued_books
Sets avail = 1 - 1 = 0                Sets avail = 1 - 1 = 0
Commit                                Commit
Final State: 2 active loans for a book that had only 1 available copy! (CORRUPTION)
```

### 5.2 Concurrency Safeguards
1. **Pessimistic Row Locking (`FOR UPDATE`):**
   In both `issue.php` and `return.php`, the target row in `books` is acquired via `SELECT ... FOR UPDATE` inside an explicit InnoDB transaction.
   - Any concurrent transaction attempting to read or update the same `book_id` is blocked until the first transaction commits or rolls back.
   - Thread 2 reads `available_copies = 0` and is safely rejected.
2. **Transaction Atomicity:**
   If the database connection drops or a query fails between the `issued_books` insert and the `books` update, the `try/catch` block invokes `$conn->rollback()`, preventing orphan loans.

---

## 6. Database Constraints and Migration Plan

### 6.1 Database Schema Enhancements (Target DDL)
To enforce the inventory model at the relational engine level, the following constraints are specified:

```sql
-- 1. Enforce physical non-negativity and upper bound on books
ALTER TABLE books
ADD CONSTRAINT chk_inventory_bounds
CHECK (available_copies >= 0 AND available_copies <= total_copies);

-- 2. Enforce minimum total copies
ALTER TABLE books
ADD CONSTRAINT chk_min_total
CHECK (total_copies >= 1);

-- 3. Add composite index on issued_books to accelerate active loan lookups
CREATE INDEX idx_issued_books_active ON issued_books (book_id, status);
```

> [!NOTE]
> MySQL 8.0.16+ and MariaDB 10.2.1+ fully support and enforce `CHECK` constraints. On older engines, constraints are syntactically parsed but not enforced; the application service layer provides the primary enforcement barrier.

### 6.2 Data Migration and Audit Script (`migrations/01_reconcile_inventory.sql`)
Before applying the `CHECK` constraint, existing legacy data must be audited and reconciled:

```sql
-- Reconcile available_copies against active loans for all books
UPDATE books b
LEFT JOIN (
    SELECT book_id, COUNT(*) AS active_count
    FROM issued_books
    WHERE status = 'issued'
    GROUP BY book_id
) l ON b.book_id = l.book_id
SET b.available_copies = b.total_copies - COALESCE(l.active_count, 0);
```

---

## 7. Traceability to Implementation Backlog

The specifications defined in this document establish the requirements for:
- **Issue 13 (GH #16) — Enforce Inventory Consistency Across Issue, Return, and Edit Operations:** Code-level implementation of the rules, form input removal, and service encapsulation.
- **Issue 17 (GH #20) — Implement Regression and Functional Tests:** Automated test cases `REG-BOOK-03`, `REG-BOOK-05`, `REG-CIRC-01`, `REG-CIRC-03`, `REG-CIRC-07`, and `REG-CIRC-08`.
