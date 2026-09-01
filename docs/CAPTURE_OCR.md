# Camera capture & OCR — implementation guide

Three features on one shared camera layer:

1. **Barcode scan** — camera reads a barcode, the product is added to the open sale or purchase.
2. **Invoice OCR** — photo of a supplier invoice becomes a *draft* purchase the buyer reviews.
3. **Receipt OCR** — photo of a receipt prefills an expense.

Recognition runs **in the browser**. The server stores images and validates the
data the user confirms; it runs no OCR, needs no queue worker and no new binary.

**One rule everywhere: capture fills a form, it never commits a record.**
Stock, debt and cash are written only by the existing services after the user
presses Save. Nothing below bypasses `PurchaseService`, `ExpenseRequest` or the
till's own submit path.

---

## 0. Prerequisites

| Requirement | Why | Check |
|---|---|---|
| HTTPS, or `localhost` | `getUserMedia` is unavailable on plain http | `docs/DEPLOYMENT.md` — if the shop runs over LAN http, live camera is impossible and only the file fallback works |
| Storage symlink | captured images are served from `storage/app/public` | `php artisan storage:link` |
| `@zxing/browser` | barcode decoding | `npm i @zxing/browser` |
| `tesseract.js` | OCR | `npm i tesseract.js` |
| Traineddata `ara`, `fra`, `eng` | Arabic/French invoices | vendored into `public/ocr/` (see §3.1) |

Both libraries are loaded with a dynamic `import()` so the till's main bundle
does not grow. Nothing is downloaded until a camera button is pressed.

---

## 1. Shared camera layer

### 1.1 `resources/js/camera.js`

```js
export async function openCamera({ mode, onResult, onClose })
```

* `mode` — `'barcode'` or `'photo'`.
* `onResult(payload)` — `{ text }` for a barcode, `{ blob, dataUrl }` for a photo.

Behaviour:

1. Render the viewfinder into the existing dialog (`#modal`, `#modal-body`) so
   the escape key, the backdrop click and the scroll lock already work.
2. `navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } } })`.
3. Draw the frame to a `<canvas>`, downscale to a 1600px long edge, export
   `image/jpeg` at 0.8 — small enough to upload on a shop connection, large
   enough for OCR.
4. **Always** stop every track on close (`stream.getTracks().forEach(t => t.stop())`).
   A live camera left running is the most visible bug this feature can ship.

### 1.2 Fallback, not an error

If `mediaDevices` is missing (http), or permission is denied, or no camera
exists: show a toast with the reason and swap the viewfinder for

```html
<input type="file" accept="image/*" capture="environment">
```

which opens the phone's own camera app. Every screen keeps working; no dead end.

### 1.3 Upload endpoint

`app/Http/Controllers/CaptureController.php`

```
POST /capture   → { path, url }
```

* Validates `image` — `required, image, mimes:jpg,jpeg,png,webp, max:4096`.
* Stores under `captures/{context}/` on the `public` disk.
* `context` is validated against a whitelist (`purchase`, `expense`, `sale`).
* Authorized per context with the permission of the screen that opened it:
  `purchase.create`, `expense.manage`, `sale.create`. Reject anything else with 403.
* Route lives in the `auth` group in `routes/web.php`.

Migration: `purchases.attachment_path` (nullable string). `expenses` already has
`attachment_path` — `ExpenseController::store` handles it today.

---

## 2. Barcode scan

**Where the button goes:** beside the product search input on `sales/create`,
`purchases/form` and `products/index`.

**Flow**

1. `openCamera({ mode: 'barcode' })` → ZXing `BrowserMultiFormatReader.decodeFromVideoDevice`,
   formats EAN-13, EAN-8, UPC-A, CODE-128, QR.
2. On decode: short beep, stop the stream, close the dialog.
3. `GET /ajax/products?q=<code>` — the existing endpoint already matches
   `barcode` and `sku` (`app/Models/Product.php:94`). No new API.
4. **Hit** → hand the product to the same `onPick` the manual search uses, so
   `window.bumpExisting` merges a repeat scan into the existing line and the
   quantity, price and totals are computed by the code already in place.
5. **Miss** → toast, and offer *"create a product with this barcode"*, which
   opens the product modal with `barcode` prefilled.

**Continuous mode** (till only): keep the camera open and keep decoding, with a
600ms guard against re-reading the same code twice. The cashier scans a basket
without touching the screen; each scan adds a line to the open sale, which the
cashier still confirms.

---

## 3. OCR pipeline

### 3.1 Worker

`resources/js/ocr.js` wraps `tesseract.js`:

* `createWorker(['ara', 'fra', 'eng'])`, `langPath` pointing at `/ocr/`.
* Vendor the `.traineddata.gz` files into `public/ocr/` — roughly 15MB total,
  fetched once and cached in IndexedDB by tesseract itself, so the shop works
  offline afterwards. Do not fetch them from a CDN: the shop is not always online.
* Runs in a Web Worker; the UI stays responsive.
* Progress callback drives the existing progress bar (`window.pageLoading`).
* One worker per page, created lazily, terminated when the dialog closes.

### 3.2 Parser — `resources/js/ocr-parse.js`

Plain functions, no DOM, unit-tested. This is where accuracy is won or lost.

Normalisation first, on every recognized line:

* Arabic-Indic digits `٠١٢٣٤٥٦٧٨٩` → ASCII.
* Thousands separators (space, thin space, `'`) stripped; `,` and `.` both
  accepted as the decimal mark — this matches how `money()` prints figures.
* Collapse repeated whitespace; drop lines shorter than 3 characters.

`parseInvoice(text)` → `{ supplier, date, lines[], confidence }`

* A line is a candidate when it ends with two or more numbers.
* Right-to-left assignment of the trailing numbers: `total`, `unit price`, `qty`.
  If only two numbers are present, treat them as `qty` and `unit price` and
  compute the total; flag the line when `qty × price ≠ total` beyond one cent.
* The leading text is the product name.
* `supplier` — best fuzzy match of the top block against the supplier list.
* `date` — first `dd/mm/yyyy`, `dd-mm-yyyy` or `yyyy-mm-dd` found.

`parseReceipt(text)` → `{ vendor, date, amount }`

* `amount` — the number following a total keyword (`TOTAL`, `المجموع`, `NET À PAYER`);
  the largest currency-shaped number is the fallback.
* `vendor` — the first line long enough to be a name.

Every field carries a confidence flag. **A field the parser is not sure about is
left empty rather than guessed** — a wrong price that looks filled-in is worse
than a blank one.

### 3.3 Review step (mandatory)

Parsed rows are written into the *normal* purchase line table, then:

* Every OCR-filled cell is tinted so the buyer sees what came from the photo.
* Rows whose product did not resolve against `/ajax/products` are marked and
  **block submit** until they are picked by hand.
* A mismatched arithmetic line is marked in the same way.
* Nothing is saved until the buyer presses the form's own Save button, which
  runs `PurchaseRequest` → `PurchaseService::create` exactly as a typed
  purchase does. Stock and supplier debt stay correct by construction.

The photo is uploaded via `/capture` and attached to the purchase as proof.

### 3.4 Receipt → expense

Same worker, `parseReceipt`, prefills the existing expense modal (amount, date,
vendor into the note). The image goes into the `attachment` field
`ExpenseController::store` already supports. User confirms, then saves.

---

## 4. Tests

**Feature (PHPUnit)**

* `/capture` stores an image and returns its path for an authorized user.
* `/capture` is 403 for a user without the context permission, 422 for a
  non-image and for a file over 4MB.
* A purchase created from a reviewed draft writes the same stock movements as a
  typed one.

**JS unit (`ocr-parse`)**

Fixtures of recognized text, asserted against expected structures:

* an Arabic invoice, a French invoice,
* Arabic-Indic digits,
* a line where `qty × price ≠ total` → flagged, not silently accepted,
* garbled OCR → fields left empty, not guessed.

---

## 5. Order of work

1. `camera.js` + `/capture` + the file fallback — unblocks all three features.
2. Barcode scan at the till — smallest change, highest daily value.
3. `ocr-parse.js` with its unit tests — the risky part, tested before it has a UI.
4. Receipt → expense — one number to get right, proves the pipeline end to end.
5. Invoice → purchase draft with the review table.

## 6. Risks

* **Arabic OCR on a phone photo of a thermal or dot-matrix invoice is
  unreliable.** The review step is the mitigation, not optional polish. Do not
  ship an auto-save path on top of it.
* Traineddata is a large first download; vendor it in `public/` and warn on
  first use that the initial scan is slow.
* Camera needs HTTPS — confirm the shop's deployment before step 1.
