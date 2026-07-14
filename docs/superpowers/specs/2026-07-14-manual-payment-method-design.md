# Manual Payment Method for Tabungan Qurban — Design Spec

## Overview

Overhaul the Qurban savings payment system by adding a **manual payment mode** as an alternative to the existing PaKasir payment gateway. Manual mode uses a static QRIS code and BSI bank transfer, requiring users to upload payment proof for admin verification.

The old payment gateway method is **preserved** (not deleted) and can be re-enabled via an admin toggle.

## Key Decisions

- **Global toggle**: Admin controls which payment mode is active for all users via `qurban_settings`
- **Single-step flow**: User sees payment instructions and uploads proof in one modal before the transaction is created — avoids "zombie" pending transactions without proof
- **Proof cleanup**: Payment proof files are deleted only during rollover (tutup buku) for shohibul who are lunas, to save storage space
- **No admin fee**: Manual mode charges exact amount (no fee from gateway)

---

## 1. Database & Backend Config

### 1.1 Migration: Add `payment_proof_path` to `qurban_transactions`

Add a nullable string column `payment_proof_path` to store the path to the uploaded payment proof file.

### 1.2 QurbanSetting Keys

Using the existing `qurban_settings` key-value table:

| Key | Default Value | Notes |
|-----|---------------|-------|
| `payment_mode` | `manual` | `'manual'` or `'gateway'` |
| `manual_qris_string` | `00020101021126640017ID.CO.BANKBSI.WWW0118936004510000200008021000002019320303UMI51440014ID.CO.QRIS.WWW0215ID10232628326460303UMI5204866153033605802ID5916DKM JAMI KASSITI6011TASIKMALAYA610546464630411B8` | Static QRIS string rendered as QR code |
| `manual_qris_name` | `DKM JAMI KASSITI` | Merchant name shown below QR |
| `manual_qris_nmid` | `ID 1023262832646` | Optional NMID |
| `manual_bank_name` | `BSI` | Bank name |
| `manual_bank_account` | `7453555555` | Account number |
| `manual_bank_holder` | `DKM Masjid Jami Kassiti` | Account holder name |

### 1.3 Public Config Endpoint

Extend `GET /qurban/config/active` response to include payment config:

```json
{
  "period": { "..." },
  "payment": {
    "mode": "manual",
    "qris_string": "0002010102...",
    "qris_name": "DKM JAMI KASSITI",
    "qris_nmid": "ID 1023262832646",
    "bank_name": "BSI",
    "bank_account": "7453555555",
    "bank_holder": "DKM Masjid Jami Kassiti"
  }
}
```

When `payment_mode = gateway`, the `payment` object only contains `{ "mode": "gateway" }`.

---

## 2. Backend Payment Flow

### 2.1 Request Validation

**`DepositRequest.php`** and **`RegisterShohibulRequest.php`** — make validation dynamic based on `payment_mode`:

- **Manual mode**:
  - `payment_method`: `in:qris,transfer_bsi`
  - `payment_proof`: `required|image|max:5120` (5MB max)
- **Gateway mode**:
  - `payment_method`: `in:qris,bri_va,bni_va,...` (unchanged)
  - No `payment_proof` field

### 2.2 Service: `QurbanTransactionService::createDeposit()`

Extend signature to accept optional proof file:

```
createDeposit(Shohibul $shohibul, int $amount, string $paymentMethod, ?UploadedFile $proofFile = null): array
```

**Manual mode logic:**
1. Upload proof via `ImageUploadService::storeAsWebp($proofFile, 'qurban-proofs')`
2. Create transaction: status=`pending`, `payment_proof_path`=stored path, `total_payment`=`amount` (no fee)
3. Do NOT call PaKasir
4. Return transaction without payment instructions (frontend already has them from config)

**Gateway mode logic:**
- Unchanged — calls PaKasir as before

### 2.3 Controller: `QurbanTransactionController::deposit()`

- Extract `payment_proof` from request as file
- Pass to service's `createDeposit()`
- Request may arrive as `multipart/form-data` (when proof is attached)

### 2.4 Verify/Cancel

Existing `verifyTransaction()` and `cancelTransaction()` in the service are unchanged — they already work correctly for pending transactions.

---

## 3. Frontend User Flow (TabunganQurban)

### 3.1 Store: Fetch Payment Config

- `fetchPeriodConfig()` in qurban store also stores `paymentConfig` from the `payment` key in response
- Store exposes `isManualPaymentMode` getter

### 3.2 Payment Method Selection (MenabungView.vue)

**Manual mode (conditional):**
- Show two options: **QRIS Masjid** (icon: QrCode) and **Transfer BSI** (icon: Landmark)
- Remove the bank VA selector grid
- Remove the "Pembayaran Mandiri Belum Tersedia" disabled state (lines 336-349)
- Submit button becomes active

**Gateway mode:**
- Current UI unchanged (QRIS + VA banks)
- Submit button remains disabled as currently implemented

### 3.3 Submit Flow (Manual Mode)

1. User clicks **"Lanjutkan Pembayaran"**
2. **Payment instruction modal** opens:
   - **If QRIS**: Render QR code from static string (reuse `qrcode.vue`), show merchant name + NMID (if set), show exact amount
   - **If Transfer BSI**: Show BSI account `7453555555` with copy button, show "DKM Masjid Jami Kassiti", show exact amount
   - Instruction text: *"Bayar tepat sesuai nominal. Tidak ada biaya admin."*
3. Below instructions: **file upload area** for payment proof
   - Accept: `image/*`
   - Max: 5MB
   - Show thumbnail preview after selection
4. **"Kirim Bukti Pembayaran"** button (disabled until file selected)
5. On submit: send `FormData` to `POST /qurban/transactions/deposit`
6. Success: toast *"Bukti pembayaran terkirim! Menunggu konfirmasi admin."* → redirect to dashboard

### 3.4 API Layer

Add `apiPostFormData(endpoint, formData)` to `api.js` — sends FormData without JSON content-type header (browser auto-sets `multipart/form-data` boundary).

Add or modify `createDeposit()` in `qurbanApi.js` to use FormData when in manual mode.

---

## 4. Admin Panel (WebDKM)

### 4.1 Settings: `QurbanPengaturanView.vue`

Add new sidebar section **"Mode Pembayaran"** with:

- **Toggle**: Manual vs Payment Gateway (radio buttons)
- **Manual config sub-form** (visible when manual selected):
  - QRIS String (textarea)
  - Merchant Name (text)
  - NMID (text, optional)
  - Bank Name (text)
  - Account Number (text)
  - Account Holder (text)
- All saved to `qurban_settings` via existing `PUT /qurban/admin/settings`

### 4.2 Transaction Detail: `QurbanSetoranView.vue`

In the receipt/verify modal, add a **payment proof section**:

- If transaction has `payment_proof_path`:
  - Display proof image (clickable for full-size view)
  - Label: "Bukti Pembayaran"
  - Positioned between transaction details and action buttons

- In the transaction table: add a small icon indicator (paperclip/image) in the method column when proof exists

### 4.3 File Storage

- Proofs stored at: `storage/app/public/qurban-proofs/`
- Accessible via: `{APP_URL}/storage/qurban-proofs/{filename}.webp`
- Filenames are random 40-char strings (from ImageUploadService) — obscure enough for security

---

## 5. Rollover — Payment Proof Cleanup

### 5.1 Logic in `RolloverService::execute()`

After cloning shohibuls to the new period, add a new step:

```
For each shohibul in the old period:
  If LUNAS (collected_amount >= target_amount):
    → Delete all payment_proof files from storage for this shohibul's transactions
    → Set payment_proof_path = null in database
  If NOT LUNAS:
    → Keep proof files (transactions continue to new period)
```

### 5.2 Error Handling

- Log count of deleted files: `"Rollover: deleted X payment proof files for lunas shohibuls"`
- If individual file deletion fails: log warning, do NOT fail the rollover
- This step runs inside the existing DB transaction, but file deletion happens after DB changes

---

## Files Changed Summary

### Backend (BackendDKM)
- **[NEW]** `database/migrations/2026_07_14_*_add_payment_proof_to_qurban_transactions.php`
- **[NEW]** `database/migrations/2026_07_14_*_seed_manual_payment_settings.php`
- **[MODIFY]** `app/Models/Qurban/QurbanTransaction.php` — add `payment_proof_path` to fillable
- **[MODIFY]** `app/Services/Qurban/QurbanTransactionService.php` — extend `createDeposit()` for manual mode
- **[MODIFY]** `app/Services/Qurban/RolloverService.php` — add proof cleanup step
- **[MODIFY]** `app/Http/Requests/Qurban/DepositRequest.php` — dynamic validation
- **[MODIFY]** `app/Http/Requests/Qurban/RegisterShohibulRequest.php` — dynamic validation
- **[MODIFY]** `app/Http/Controllers/Api/V1/Qurban/QurbanTransactionController.php` — pass proof file to service
- **[MODIFY]** `app/Http/Controllers/Api/V1/Qurban/PeriodController.php` — include payment config in active response

### Frontend (TabunganQurban)
- **[MODIFY]** `src/services/api.js` — add `apiPostFormData()`
- **[MODIFY]** `src/services/qurbanApi.js` — update `createDeposit()` for FormData
- **[MODIFY]** `src/stores/qurban.js` — store payment config, add getter
- **[MODIFY]** `src/views/MenabungView.vue` — conditional payment UI, upload flow, manual payment modal

### Admin (WebDKM)
- **[MODIFY]** `src/views/admin/QurbanPengaturanView.vue` — add payment mode toggle + config form
- **[MODIFY]** `src/views/admin/QurbanSetoranView.vue` — show payment proof in modal, add indicator in table
