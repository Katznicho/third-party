# Kashtre → Third party: Record client portion after “Collect payment”

When the user clicks **Collect payment** in Kashtre and the payment is **completed**, Kashtre should call the third-party API so the payment is reflected on the insurer’s side. Behavior by environment:

- **Production (`APP_ENV=production`):** Call Yo for real mobile money. When Yo confirms payment → **complete the payment** in Kashtre → then do the rest (e.g. update invoice, call third party to record client portion). No auto-complete; payment is completed only after Yo success.
- **Local / non-production:** Don’t call Yo. **Auto-complete** the payment when the user clicks Collect payment → then do the rest (same: update invoice, call third party to record client portion).

So in both cases, “the rest” is the same: once the payment is completed (either by Yo in production or auto locally), call the third-party endpoint below so the money is reflected:

- On the **third-party account** (money received)
- On the **client’s internal account** in the third party (ClientAccount + Transaction)
- In **Payments** on the third party

## Third-party endpoint (implemented)

- **URL:** `POST {THIRD_PARTY_BASE_URL}/api/v1/payments/record-client-portion`
- **Auth:** Same as other Kashtre→third-party calls (if any).
- **Body (JSON):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `insurance_company_id` | int | Yes | Third-party `insurance_companies.id` (the insurer’s internal ID). |
| `policy_number` | string | Yes | Client’s policy number. |
| `amount` | number | Yes | Client portion amount collected. |
| `payment_reference` | string | Yes | Unique reference (e.g. `KASHTRE-CP-{invoice_id}-{timestamp}`). Must be unique across all payments. |
| `kashtre_invoice_id` | string | No | Kashtre invoice ID. |
| `authorization_reference` | string | No | Authorization reference from the insurance authorization. |
| `connected_business_id` | int | No | Kashtre business id (vendor). Stored in payment metadata so the payment appears on `/third-party-vendors/{id}`. |
| `payment_method` | string | No | One of: `cash`, `bank_transfer`, `mobile_money`, `cheque`, `card`, `credit`, `other`. Default `mobile_money`. |
| `mobile_money_number` | string | No | Mobile money number if applicable. |

- **Success:** `201` with `{ "success": true, "message": "...", "data": { "payment_id", "transaction_id" } }`.
- **Errors:** `404` (policy/client not found), `422` (validation, e.g. duplicate `payment_reference`), `500` (server error).

## When Kashtre should call it

Call this endpoint **once** after the payment is **completed**:

- **Production:** After Yo confirms the payment and Kashtre marks the payment as completed (e.g. after `processMobileMoneyPayment` / Yo callback indicates success).
- **Local:** After the local flow auto-completes the payment (no Yo call).

Use the invoice’s saved data, e.g.:

- `insurance_authorization_snapshot.client_total` or the amount you actually collected
- Client’s `policy_number` and the insurer’s third-party `insurance_company_id`
- `insurance_authorization_snapshot.insurance_authorization_reference` (or similar) for `authorization_reference`
- Invoice id for `kashtre_invoice_id`

Generate a unique `payment_reference` per call (e.g. `KASHTRE-CP-{invoice_id}-{time()}` or with a UUID). Do not block the success UI on this call: fire-and-forget or show a warning on failure, but still treat the Kashtre payment as completed.

## Example request (from Kashtre)

```http
POST /api/v1/payments/record-client-portion
Content-Type: application/json

{
  "insurance_company_id": 1,
  "policy_number": "POL-2024-001",
  "amount": 130000,
  "payment_reference": "KASHTRE-CP-12345-1739123456",
  "kashtre_invoice_id": "12345",
  "authorization_reference": "AUTH-REF-XXX",
  "payment_method": "mobile_money",
  "mobile_money_number": "256712345678"
}
```

After a successful call, the third party will have created a `Payment`, a `Transaction` on the client’s account, and updated the client’s `ClientAccount` balance. These 4 sections then show the updated data:

1. **`/clients/{id}/account-statement`** – Client account statement (transactions + balance).
2. **`/payments`** – Payments list (new payment row).
3. **`/third-party-vendors/{id}`** – Vendor payments (payments from Kashtre; optional filter by `connected_business_id`).
4. **`/balance-statement/{id}`** – Redirects to client account statement for that client (same as (1)).

---

## Testing the third-party endpoint (without Kashtre)

You can test that the third party correctly records the payment using curl or Postman.

1. **Ensure you have a policy** in the third-party DB: same `insurance_company_id` and `policy_number` you’ll send, with a principal member (client).
2. **Pick a unique `payment_reference`** (e.g. `KASHTRE-CP-TEST-1-1739123456`). It must not already exist in `payments.payment_reference`.
3. **Call the API** (replace base URL and values):

```bash
curl -X POST "http://your-third-party.test/api/v1/payments/record-client-portion" \
  -H "Content-Type: application/json" \
  -d '{
    "insurance_company_id": 1,
    "policy_number": "YOUR_POLICY_NUMBER",
    "amount": 50000,
    "payment_reference": "KASHTRE-CP-TEST-1-'$(date +%s)'",
    "kashtre_invoice_id": "999",
    "authorization_reference": "AUTH-TEST",
    "payment_method": "mobile_money"
  }'
```

4. **Verify:** In the third-party app, check **Payments** (new row) and the **client’s account** (balance/transactions) for that policy’s principal member.

**Full E2E (Collect payment in Kashtre → third party updated):** Implement in Kashtre: after payment is completed (Yo success in production or auto-complete locally), call this same endpoint with the invoice’s client portion data. Then test by doing a full Collect payment in Kashtre and confirming the payment appears in the third party.

---

## Third party → Kashtre: Notify so Kashtre’s 2 sections update

Two of the four sections live **in Kashtre** (e.g. Kashtre’s payments list and Kashtre’s client account statement). When the third party records a client-portion payment (via the form or via the API above), it **calls Kashtre’s API** so Kashtre can update those 2 sections.

**Kashtre must expose:**

- **URL:** `POST {KASHTRE_BASE}/api/v1/third-party/client-portion-recorded`  
  (Override with `KASHTRE_RECORD_CLIENT_PORTION_PATH` in the third party’s `.env` if needed.)
- **Body (JSON):**  
  `insurance_company_id`, `policy_number`, `amount`, `payment_reference`, `client_id` (third-party client id), optional: `kashtre_invoice_id`, `authorization_reference`, `mobile_money_number`, `payment_date`.

**What Kashtre should do when it receives this:** Create or update the payment record in Kashtre and update the client’s account/statement so the payment appears in (1) Kashtre’s payments list and (2) Kashtre’s client account statement. The third party calls this automatically after recording the payment locally.
