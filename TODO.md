# Plan: Refactor Database Schema for Debt Cases and Debtors

## Summary
The current schema incorrectly models Debtors as owning Debts, but business logic requires Debt Cases (with case numbers and one sum) as primary entities. Each Debt Case can have multiple Debtors, and Debtors can be associated with multiple Cases. Payments are made against Debt Cases, optionally attributed to specific Debtors.

**Recommended approach:**
1. Move `case_number` from `debtors` table to `debts` table.
2. Remove `debtor_id` from `debts` table.
3. Create `debt_debtor` pivot table for many-to-many relationship.
4. Make `debtor_id` in `payments` table nullable.
5. Migrate existing data to new structure.
6. Update models, controllers, and frontend accordingly.

---

## Implementation Steps

### Phase 0: Change AGENTS.md to match Summary logic.

### Phase 1: Schema Changes and Data Migration
**Goal:** Update database structure and migrate existing data.

1. **Create new migration: `create_debt_debtor_table.php`**
   - Pivot table: `debt_id`, `debtor_id`, timestamps.
   - Unique constraint on (`debt_id`, `debtor_id`).

2. **Create migration: `refactor_debts_and_debtors_schema.php`**
   - Add `case_number` column to `debts` table.
   - Make `debtor_id` in `payments` table nullable.
   - Migrate data: For each existing debt, set `case_number` from associated debtor, and insert into `debt_debtor` pivot.
   - Drop `debtor_id` from `debts` table.
   - Drop `case_number` from `debtors` table.

3. **Run migrations and verify data integrity**
   - Ensure all debts have case_number migrated.
   - Ensure pivot table populated correctly.
   - Check payments still link properly.

### Phase 2: Update Backend Models and Relations
**Goal:** Reflect new many-to-many relationship in Eloquent models.

4. **Update `Debt` model (`backend/app/Models/Debt.php`)**
   - Remove `debtor()` relation.
   - Add `debtors()` many-to-many relation.
   - Add `case_number` to fillable.
   - Remove `debtor_id` from fillable.

5. **Update `Debtor` model (`backend/app/Models/Debtor.php`)**
   - Add `debts()` many-to-many relation.
   - Remove `case_number` from fillable.

6. **Update `Payment` model (`backend/app/Models/Payment.php`)**
   - Make `debtor_id` nullable in fillable/casts if needed.
   - Ensure `debtor()` relation handles nulls.

7. **Update Policies and Controllers**
   - Adjust authorization logic for debts (now not tied to single debtor).
   - Update API endpoints to handle debt-debtor associations.

### Phase 3: Update Domain Logic and Actions
**Goal:** Modify business logic to work with new schema.

8. **Update Domain Actions (`backend/app/Domain/Debt/Actions/`)**
   - Modify create/update/list actions to handle multiple debtors per debt.
   - Ensure case_number is set on debt creation.

9. **Update Domain Actions (`backend/app/Domain/Debtor/Actions/`)**
   - Adjust actions to work with many-to-many debts.

10. **Update DTOs and Events**
    - Modify `DebtorData` and `DebtData` to reflect new structure.

### Phase 4: Update API and Frontend
**Goal:** Adapt API responses and frontend components.

11. **Update API Resources (`backend/app/Http/Resources/`)**
    - `DebtResource`: Include debtors array instead of single debtor.
    - `DebtorResource`: Include debts array.

12. **Update Controllers (`backend/app/Http/Controllers/Api/V1/`)**
    - `DebtorController`: Handle debt associations.
    - `DebtController`: Handle debtor associations.

13. **Update Frontend API (`frontend/src/api/`)**
    - Modify `debtors.ts` and `debts.ts` to handle new relations.
    - Update types for Debtor and Debt interfaces.

14. **Update Frontend Pages and Components**
    - Adjust debtor/debt detail pages to show multiple associations.
    - Update forms for creating debts with multiple debtors.

### Phase 5: Update Tests and Validation
**Goal:** Ensure all tests pass with new schema.

15. **Update Feature Tests (`backend/tests/Feature/Api/`)**
    - Modify tests to reflect new relationships.
    - Add tests for many-to-many associations.

16. **Update Unit Tests**
    - Adjust domain logic tests.

17. **Run full test suite**
    - Ensure no regressions.

---

## Relevant Files

- `backend/database/migrations/` — New migrations for schema changes
- `backend/app/Models/Debt.php`, `Debtor.php`, `Payment.php` — Model updates
- `backend/app/Domain/Debt/`, `backend/app/Domain/Debtor/` — Action and DTO updates
- `backend/app/Http/Controllers/Api/V1/`, `backend/app/Http/Resources/` — API updates
- `frontend/src/api/debtors.ts`, `frontend/src/api/debts.ts` — Frontend API updates
- `frontend/src/pages/` — Page component updates

---

## Verification

1. **Data migration verification:**
   - All debts have case_number populated.
   - Pivot table has correct associations.
   - No orphaned payments.

2. **API endpoint testing:**
   - Creating debt with multiple debtors works.
   - Listing debtors for a debt works.
   - Payments can be created without debtor.

3. **Frontend functionality:**
   - Debt detail page shows associated debtors.
   - Debtor detail page shows associated debts.
   - Payment forms handle optional debtor selection.

4. **Business logic validation:**
   - Case numbers are unique per district.
   - Debtors can be shared across debts.
   - Payments aggregate correctly per debt case.

---

## Decisions & Scope

**Included:**
- Schema refactoring to Debt-centric model.
- Many-to-many Debt-Debtor relationship.
- Optional Debtor on Payments.
- Data migration for existing records.

**Excluded:**
- Changes to District or User models.
- Frontend UI redesign beyond necessary updates.
- Performance optimizations (e.g., indexing).

**Assumptions:**
- Existing data can be migrated without conflicts.
- Case numbers are unique within districts.
- Payments without debtor are allowed.

**Risks:**
- Data loss during migration if not handled carefully.
- Breaking changes to API consumers.
- Complex frontend updates for many-to-many relations.

---

## Dependencies & Parallelism

- **Phase 1 must precede all others:** Schema changes first.
- **Phases 2-3 can be parallel:** Backend updates independent of frontend.
- **Phase 4 depends on 2-3:** Frontend needs updated backend.
- **Phase 5 depends on all:** Testing last.

**Estimated effort:** High — involves schema changes, data migration, and full-stack updates.