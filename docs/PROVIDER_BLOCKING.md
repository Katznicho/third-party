# API Connection Validation - Suspended Provider Prevention

## Implementation

### 1. Middleware: ValidateConnectionStatus
**File**: `app/Http/Middleware/ValidateConnectionStatus.php`

This middleware validates that a connection is active before processing critical API requests.

**Logic**:
- Extracts `insurance_company_id` and `connected_business_id` from request
- Looks up the `BusinessConnection` in the database
- Checks connection status:
  - If `suspended`: Returns 403 with "suspended" status
  - If `blocked`: Returns 403 with "blocked" status
  - If `active`: Allows request to proceed

**Response Format** (when blocked/suspended):
```json
{
  "success": false,
  "message": "This provider connection is currently suspended and cannot process requests.",
  "status": "suspended",
  "reason": "Reason provided by admin",
  "suspended_at": "2026-04-08T10:30:00Z"
}
```

### 2. Protected API Routes
**File**: `routes/api.php`

The following critical endpoints now validate connection status:

#### Payment Processing
- `POST /api/v1/payments/responsibility` - Create payment responsibility
- `POST /api/v1/payments/record-client-portion` - Record client payment

#### Authorization
- `POST /api/v1/invoice-authorization/request` - Request authorization decision

#### Client & Visit Management
- `POST /api/v1/clients/sync` - Sync client data
- `POST /api/v1/authorized-visits/register` - Register authorized visit

### 3. Public Routes (No Validation)
The following remain public and don't validate connection status (as they're needed for initial registration):
- Business registration
- Policy verification
- Client verification
- User creation
- Connection creation

## End-to-End Flow

### Scenario: Suspend a Provider

```
1. Insurance Admin @ http://localhost:8001/connected-companies/2
   ├─ Clicks "Suspend" button
   ├─ Fills in reason: "Non-compliance with requirements"
   └─ Submits form

2. Database Update (third-party)
   ├─ UPDATE business_connections SET
   │  ├─ status = 'suspended'
   │  ├─ block_reason = 'Non-compliance with requirements'
   │  ├─ blocked_at = NOW()
   │  └─ blocked_by = [admin_user_id]
   └─ Connection is now SUSPENDED

3. Kashtre Tries to Request Authorization
   ├─ Calls: POST /api/v1/invoice-authorization/request
   ├─ Sends: insurance_company_id=5, other fields...
   │
   └─ Third-Party API Handler:
      ├─ ValidateConnectionStatus middleware triggers
      ├─ Finds business_connection (id=5)
      ├─ Checks: connection.isSuspended() → TRUE
      ├─ Returns 403 JSON response
      └─ Request is REJECTED

4. Kashtre Error Handling
   ├─ Receives 403 response
   ├─ Logs error with reason
   ├─ Displays error to user: "Provider is currently suspended"
   └─ Authorization FAILS

5. Same for All Protected Endpoints
   ├─ /payments/responsibility → 403 Rejected
   ├─ /payments/record-client-portion → 403 Rejected
   ├─ /clients/sync → 403 Rejected
   ├─ /authorized-visits/register → 403 Rejected
   └─ All operations BLOCKED while suspended
```

## Verification Steps

### 1. Suspend a Connection
```bash
# Via UI at: http://localhost:8001/connected-companies/2
# Fill in reason and click "Suspend"
```

### 2. Try to Call Protected Endpoint
```bash
curl -X POST http://localhost:8001/api/v1/invoice-authorization/request \
  -H "Content-Type: application/json" \
  -d '{
    "insurance_company_id": 5,
    "kashtre_invoice_id": 123,
    "invoice_number": "INV-001",
    "policy_number": "POL-123",
    "total_amount": 100000
  }'

# Expected Response (403 Forbidden):
{
  "success": false,
  "message": "This provider connection is currently suspended and cannot process requests.",
  "status": "suspended",
  "reason": "Non-compliance with requirements",
  "suspended_at": "2026-04-08T10:30:00Z"
}
```

### 3. Verify Kashtre Integration
When Kashtre tries to request authorization:
```bash
# In Kashtre app logs, check:
# - HTTP 403 response from third-party API
# - Error message shows "suspended" or "blocked"
# - User sees error: "Provider connection is suspended"
```

### 4. Reactivate Connection
```bash
# Via UI at: http://localhost:8001/connected-companies/2
# Click "Reactivate Connection" button
```

### 5. Verify Access Restored
```bash
# Same API call now works:
curl -X POST http://localhost:8001/api/v1/invoice-authorization/request \
  -H "Content-Type: application/json" \
  -d '{ ... }'

# Expected Response (200 OK):
{
  "success": true,
  "authorization_reference": "AUTH-12345",
  "client_total": 30000,
  "insurance_total": 70000
}
```

## Security Features

✅ **Real-time Validation**: Connection status checked on every request
✅ **No Request Processing**: Suspended connections rejected before business logic
✅ **Clear Error Messages**: Providers informed why request was rejected
✅ **Audit Trail**: Reason and who suspended is tracked
✅ **Instant Effect**: No caching delays, effective immediately
✅ **All Critical Operations**: Payment, authorization, sync, visits all protected
✅ **Graceful Degradation**: Providers can still register/verify policies

## Database Schema

```sql
ALTER TABLE business_connections ADD COLUMN (
  status ENUM('active', 'suspended', 'blocked') DEFAULT 'active',
  block_reason VARCHAR(1000) NULL,
  blocked_at TIMESTAMP NULL,
  blocked_by BIGINT UNSIGNED NULL
);
```

## Files Modified

1. Created: `app/Http/Middleware/ValidateConnectionStatus.php`
2. Modified: `routes/api.php` - Added middleware to protected routes
3. Modified: `app/Models/BusinessConnection.php` - Added status methods (already done)
4. Modified: `app/Http/Controllers/ConnectedCompaniesController.php` - Added block/reactivate (already done)

## What Happens When Suspended/Blocked?

| Operation | Status | Result | Error Code |
|-----------|--------|--------|-----------|
| Request Authorization | Suspended | REJECTED | 403 Forbidden |
| Record Payment | Suspended | REJECTED | 403 Forbidden |
| Sync Client | Suspended | REJECTED | 403 Forbidden |
| Register Visit | Suspended | REJECTED | 403 Forbidden |
| Create Payment Responsibility | Suspended | REJECTED | 403 Forbidden |
| **Any blocked operation** | **Blocked** | **REJECTED** | **403 Forbidden** |

## No Impact On

- Registration endpoints (public)
- Policy verification (public)
- Client searches (public)
- Business information lookup (public)
