# Insurance Authorization & Payment System Architecture

## Overview
This document outlines the architecture for a comprehensive insurance authorization and payment system that handles automatic/manual approvals, multi-payer scenarios, partial coverage, and settlement processing.

## Current State Analysis

### Existing Infrastructure
✅ Pre-authorizations table with status management
✅ Pre-authorization triggers for auto-creation
✅ Invoice system with `coverage_decision` field
✅ Payment processing for insurance companies
✅ Third-party payer balance tracking

### Gaps to Address
❌ Automatic authorization rules engine
❌ Manual authorization workflow
❌ Multi-payer support (multiple insurance companies per transaction)
❌ Partial coverage with fallback payment methods
❌ Settlement/batch payment system for insurance companies

---

## Proposed Architecture

### 1. Authorization Workflow System

#### 1.1 Authorization Rules Engine
**Purpose**: Automatically approve/reject transactions based on configurable rules

**Components**:
- **AuthorizationRule** model: Stores rules for automatic processing
- **AuthorizationRuleEngine** service: Evaluates rules and makes decisions
- **Rule Types**:
  - Amount-based (auto-approve if < X, auto-reject if > Y)
  - Service category-based (auto-approve certain categories)
  - Policy-based (auto-approve based on policy type/coverage)
  - Client-based (auto-approve for VIP clients)
  - Time-based (auto-approve during business hours)
  - Risk-based (flag for review if risk score > threshold)

**Database Schema**:
```sql
authorization_rules:
  - id
  - insurance_company_id
  - rule_name
  - rule_type (amount, service_category, policy_type, client_tier, time_based, risk_based)
  - conditions (JSON) - flexible conditions
  - action (auto_approve, auto_reject, flag_for_review)
  - priority (for rule ordering)
  - is_active
  - created_at, updated_at
```

#### 1.2 Authorization Workflow States
```
PENDING → [Auto/Manual Processing] → APPROVED | REJECTED | FLAGGED_FOR_REVIEW
                                      ↓
                              FLAGGED_FOR_REVIEW → MANUAL_REVIEW → APPROVED | REJECTED
```

**Status Flow**:
- `pending`: Initial state, awaiting processing
- `auto_approved`: Automatically approved by rules engine
- `auto_rejected`: Automatically rejected by rules engine
- `flagged_for_review`: Requires manual review
- `manually_approved`: Approved by human reviewer
- `manually_rejected`: Rejected by human reviewer
- `partially_approved`: Approved for partial amount

#### 1.3 Manual Authorization Interface
**Features**:
- Dashboard showing flagged transactions
- Review queue with filters (by amount, service category, risk score)
- Quick approve/reject actions
- Notes and reason tracking
- Bulk operations for similar cases

---

### 2. Multi-Payer System

#### 2.1 Multiple Insurance Providers per Transaction
**Scenario**: One visit covered by multiple insurance companies (e.g., 50% Jubilee, 50% Prudential)

**Database Schema**:
```sql
invoice_insurance_allocations:
  - id
  - invoice_id
  - insurance_company_id
  - policy_id
  - allocation_percentage (e.g., 50.00)
  - allocated_amount (calculated)
  - status (pending, approved, rejected)
  - approval_id (if pre-authorized)
  - created_at, updated_at

payment_allocations:
  - id
  - payment_id
  - invoice_insurance_allocation_id
  - amount
  - payment_method (insurance, cash, mobile_money)
  - status
  - created_at, updated_at
```

#### 2.2 Partial Coverage with Fallback
**Scenario**: Insurance covers 50%, remaining 50% by cash/mobile money

**Flow**:
1. Invoice created: UGX 1,000,000
2. Insurance allocation: 50% = UGX 500,000 (from Insurance Company A)
3. Remaining balance: UGX 500,000
4. Client pays remaining via cash/mobile money
5. System tracks both payments separately

**Implementation**:
- Invoice has `insurance_coverage_amount` and `client_payment_amount`
- Payment processing splits between insurance and client payment methods
- Receipts show breakdown

---

### 3. Settlement System

#### 3.1 Batch Settlement Processing
**Purpose**: Insurance companies (Jubilee, Prudential) settle outstanding bills with providers

**Components**:
- **SettlementBatch** model: Groups invoices for settlement
- **SettlementItem** model: Individual invoice in a batch
- **SettlementPayment** model: Payment made against a batch

**Database Schema**:
```sql
settlement_batches:
  - id
  - insurance_company_id
  - batch_number (unique)
  - total_amount
  - invoice_count
  - status (draft, submitted, approved, paid, rejected)
  - submitted_by
  - submitted_at
  - approved_by
  - approved_at
  - payment_reference
  - payment_date
  - created_at, updated_at

settlement_items:
  - id
  - settlement_batch_id
  - invoice_id
  - invoice_number
  - amount
  - status
  - created_at, updated_at

settlement_payments:
  - id
  - settlement_batch_id
  - payment_method (bank_transfer, mobile_money, cheque)
  - payment_reference
  - amount
  - payment_date
  - proof_of_payment_path
  - status (pending, confirmed, failed)
  - created_at, updated_at
```

#### 3.2 Settlement Workflow
```
1. Provider creates settlement batch (selects unpaid invoices)
2. Batch submitted to insurance company
3. Insurance company reviews and approves/rejects
4. Insurance company makes payment
5. Provider confirms payment receipt
6. System updates invoice statuses and balances
```

---

## Implementation Plan

### Phase 1: Authorization Rules Engine
**Priority**: High
**Timeline**: 2-3 weeks

1. Create `authorization_rules` table
2. Build `AuthorizationRule` model
3. Create `AuthorizationRuleEngine` service
4. Integrate with pre-authorization creation
5. Add rule management UI in settings

### Phase 2: Manual Authorization Workflow
**Priority**: High
**Timeline**: 1-2 weeks

1. Create authorization review dashboard
2. Build review queue with filters
3. Implement approve/reject actions
4. Add notes and audit trail
5. Notification system for reviewers

### Phase 3: Multi-Payer Support
**Priority**: Medium
**Timeline**: 2-3 weeks

1. Create `invoice_insurance_allocations` table
2. Update invoice creation to support multiple insurers
3. Modify payment processing for split payments
4. Update UI to show multiple insurance allocations
5. Generate receipts with breakdown

### Phase 4: Partial Coverage & Fallback
**Priority**: Medium
**Timeline**: 1-2 weeks

1. Update invoice model for partial coverage
2. Modify payment processing to handle split payments
3. Update client payment UI
4. Generate receipts showing insurance + client payment

### Phase 5: Settlement System
**Priority**: Medium
**Timeline**: 2-3 weeks

1. Create settlement batch tables
2. Build batch creation UI (select invoices)
3. Create settlement submission workflow
4. Build insurance company review interface
5. Implement payment confirmation process
6. Auto-update invoice statuses on settlement

---

## Key Design Decisions

### 1. Rule Engine Architecture
**Option A**: Simple if-then rules (easier to implement)
**Option B**: Complex rule engine with conditions (more flexible)
**Recommendation**: Start with Option A, evolve to Option B

### 2. Multi-Payer Allocation
**Option A**: Percentage-based (50%, 50%)
**Option B**: Amount-based (UGX 500,000 each)
**Recommendation**: Support both - percentage is primary, amount is fallback

### 3. Settlement Frequency
**Option A**: Real-time (each invoice settled individually)
**Option B**: Batch (weekly/monthly batches)
**Recommendation**: Support both - batch is primary for efficiency

### 4. Partial Coverage Payment
**Option A**: Client pays remaining at point of service
**Option B**: Client pays remaining later (credit)
**Recommendation**: Support both - immediate payment preferred

---

## Database Schema Changes

### New Tables Needed:
1. `authorization_rules` - Rules for auto-approval/rejection
2. `authorization_audit_logs` - Track all authorization decisions
3. `invoice_insurance_allocations` - Multiple insurance per invoice
4. `payment_allocations` - Split payments across methods
5. `settlement_batches` - Batch settlement groups
6. `settlement_items` - Invoices in a batch
7. `settlement_payments` - Payments against batches

### Tables to Modify:
1. `pre_authorizations` - Add `authorization_method` (auto/manual)
2. `invoices` - Add `insurance_coverage_amount`, `client_payment_amount`
3. `payments` - Add `allocation_type` (full, partial, settlement)

---

## API Endpoints Needed

### Authorization:
- `POST /api/v1/pre-authorizations/{id}/auto-process` - Trigger auto-processing
- `POST /api/v1/pre-authorizations/{id}/approve` - Manual approve
- `POST /api/v1/pre-authorizations/{id}/reject` - Manual reject
- `GET /api/v1/pre-authorizations/flagged` - Get flagged for review

### Multi-Payer:
- `POST /api/v1/invoices/{id}/allocate-insurance` - Allocate to insurance
- `GET /api/v1/invoices/{id}/allocations` - Get allocations
- `POST /api/v1/invoices/{id}/process-split-payment` - Process split payment

### Settlement:
- `POST /api/v1/settlements/create-batch` - Create settlement batch
- `POST /api/v1/settlements/{id}/submit` - Submit to insurance company
- `POST /api/v1/settlements/{id}/approve` - Insurance company approves
- `POST /api/v1/settlements/{id}/process-payment` - Process payment

---

## User Interfaces Needed

### Third-Party System (Insurance Companies):
1. **Authorization Rules Management** (`/settings/authorization-rules`)
   - Create/edit/delete rules
   - Test rules
   - View rule execution history

2. **Authorization Review Queue** (`/authorizations/review`)
   - List of flagged transactions
   - Filters and search
   - Quick approve/reject
   - Bulk operations

3. **Settlement Management** (`/settlements`)
   - View submitted batches
   - Approve/reject batches
   - Process payments
   - View payment history

### Kashtre System (Providers):
1. **Multi-Payer Invoice Creation** (`/invoices/create`)
   - Select multiple insurance companies
   - Set allocation percentages
   - Set fallback payment methods

2. **Settlement Batch Creation** (`/settlements/create`)
   - Select unpaid invoices
   - Create batch
   - Submit to insurance company

3. **Payment Processing** (Enhanced)
   - Show insurance allocation
   - Show remaining balance
   - Process client payment for remaining

---

## Questions for Discussion

1. **Authorization Rules**: What are the specific rules for auto-approval/rejection? (amount thresholds, service categories, etc.)

2. **Multi-Payer Limits**: Maximum number of insurance companies per transaction? (e.g., max 2?)

3. **Settlement Frequency**: How often should insurance companies settle? (weekly, monthly, on-demand?)

4. **Partial Coverage**: Should client pay remaining immediately or can it be deferred?

5. **Notification System**: How should reviewers be notified of flagged transactions? (email, SMS, in-app?)

6. **Audit Requirements**: What level of audit trail is needed for compliance?

7. **Integration**: Do insurance companies have existing systems we need to integrate with?

---

## Next Steps

1. **Review this architecture** with stakeholders
2. **Clarify requirements** for each component
3. **Prioritize phases** based on business needs
4. **Start with Phase 1** (Authorization Rules Engine)
5. **Iterate and refine** based on feedback

---

## Technical Considerations

### Performance:
- Rule engine should be fast (cache rules)
- Batch settlements should handle large volumes
- Payment processing should be transactional

### Security:
- Authorization decisions should be auditable
- Settlement approvals should require proper permissions
- Payment processing should be secure

### Scalability:
- Rules engine should handle complex conditions
- Multi-payer should support any number of insurers
- Settlement system should handle high transaction volumes

---

## Example Scenarios

### Scenario 1: Auto-Approval
1. Client visits provider, bill = UGX 100,000
2. System checks rules: Amount < UGX 500,000 → Auto-approve
3. Pre-authorization created with status = `auto_approved`
4. Invoice created and marked as approved
5. Insurance company notified

### Scenario 2: Flagged for Review
1. Client visits provider, bill = UGX 1,500,000
2. System checks rules: Amount > UGX 1,000,000 → Flag for review
3. Pre-authorization created with status = `flagged_for_review`
4. Reviewer sees in queue, reviews, approves
5. Invoice created and marked as approved

### Scenario 3: Multi-Payer (50/50)
1. Client has 2 insurance policies (Jubilee 50%, Prudential 50%)
2. Invoice created: UGX 1,000,000
3. System allocates: Jubilee = UGX 500,000, Prudential = UGX 500,000
4. Both insurance companies process their portions
5. Receipt shows breakdown

### Scenario 4: Partial Coverage (50% Insurance, 50% Cash)
1. Invoice created: UGX 1,000,000
2. Insurance covers: UGX 500,000
3. Remaining: UGX 500,000
4. Client pays UGX 500,000 via cash
5. Receipt shows: Insurance UGX 500,000 + Cash UGX 500,000

### Scenario 5: Settlement Batch
1. Provider selects 50 unpaid invoices (total UGX 5,000,000)
2. Creates settlement batch
3. Submits to Jubilee Insurance
4. Jubilee reviews and approves
5. Jubilee makes payment (bank transfer)
6. Provider confirms receipt
7. All 50 invoices marked as paid

---

## Conclusion

This architecture provides a comprehensive solution for:
- ✅ Automatic and manual authorization
- ✅ Multi-payer support
- ✅ Partial coverage with fallback
- ✅ Settlement processing

The phased approach allows for incremental development and testing, ensuring each component works well before moving to the next.

**Recommendation**: Start with Phase 1 (Authorization Rules Engine) as it provides immediate value and is foundational for other features.
