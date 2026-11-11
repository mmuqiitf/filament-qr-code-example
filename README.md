# filament-qr-code example app

A Filament v5 panel that shows [mmuqiitf/filament-qr-code](https://github.com/mmuqiitf/filament-qr-code) (`^1.0`) in realistic flows.

## Setup

```bash
composer setup        # install, .env, key, migrate, npm build
php artisan db:seed   # optional demo data
composer dev          # serve on http://localhost:8000/admin
```

## Pages

| Page | URL | Shows |
|---|---|---|
| Scanner Showcase | `/admin/scanner-showcase` | `QrScanner` options: formats, upload, focus handoff |
| Server-side Checks | `/admin/server-side-checks` | `validateUsing()`, `lookup()`, accept/reject feedback |
| Cashier POS Checkout | `/admin/cashier-checkout` | `QrHardwareScannerListener` cart with a handheld scanner |
| POS Checkout | `/admin/pos-checkout` | Continuous, page-wide scanning (see below) |
| Sequential Scanning | `/admin/work-order-sequence` | Plain `QrScanSequence` checklist |
| Goods Receiving | `/admin/goods-receiving` | Checked, wedge-driven sequence (see below) |
| Manufacturing | `/admin/manufacturing` | Work-order station: every extension point (see below) |
| Interactive QR Customizer | `/admin/qr-customizer` | Generating and styling QR codes |
| QR Labels | `/admin/qr-labels` | Printable label sheets for the demo codes, built on the generator API |
| Barcode Showcase | `/admin/barcode-showcase` | 1D barcodes (Code 128, EAN-13, EAN-8, UPC-A, Code 39, ITF-14, GS1-128) with `BarcodeDisplay`, product EAN-13 labels, inline errors for invalid values (needs filament-qr-code 1.1; GS1-128 needs 1.2) |
| GS1 Receiving | `/admin/gs1-receiving` | `QrScanner::gs1()`: one GS1 label fills a received stock line (see below; needs filament-qr-code 1.2) |
| GS1 Labels | `/admin/gs1-labels` | GS1-128 sample labels to scan into GS1 Receiving, valid and invalid (needs filament-qr-code 1.2) |

### POS Checkout

`QrScanner::mode(ScanMode::Continuous)` with `hardwareInput(scope: HardwareInputScope::Page)`: shoot barcodes anywhere on the page without clicking the field. Each accepted scan runs a `Product` lookup and adds a cart line; scanning the same SKU again bumps its quantity (the page merges lines in `addToCart()`). Try `PRD-1001` twice and `PRD-9999` (unknown SKU). The options toggle `continuousDisplay()`, `pauseOnReject()` and `keepCameraOpen()`; they apply in place on the next re-render (no `wire:key` re-mount). `normalizeWith(uppercase: true)` lets `prd-1001` match, and the Pause/Resume buttons send `qr-scanner-pause` / `qr-scanner-resume` window events.

### Goods Receiving

A `QrScanSequence` built from `ScanStep`s, driven by a handheld scanner without focusing anything:

1. **Purchase order**: `sticky()`, so it stays for every item on the PO. Use **Clear** to change it.
2. **SKU**: `lookup(Product::class, 'sku')`.
3. **Lot**: `optional()`. Without a lot label, scan the first serial straight away: the lot gives way to the serials step.
4. **Serial numbers**: `repeat(n)`, one per unit ("Units per item"), with duplicates rejected and a remove button per code.

Once the item is complete, `validateAllUsing()` checks that every serial belongs to the SKU (`SN-PRD-1001-…`) and jumps back to the serials if not. With "Receive automatically" on, `autoSubmit()` calls `submit()` and `resetAfterSubmit()` clears everything but the PO; with it off, press **Receive item** (a normal submit re-runs every check, then `resetSequence()` keeps the PO). Try `PO-7001`, `PRD-1001`, `LOT-A1`, `SN-PRD-1001-0001`, `SN-PRD-1001-0002`.

### GS1 Receiving

Receiving against GS1 logistic labels (`app/Filament/Pages/Gs1Receiving.php`). One `QrScanner::gs1()` field reads a GS1-128, GS1 DataMatrix or GS1 QR label from a handheld scanner (`hardwareInput(scope: HardwareInputScope::Page)`, no need to focus the field), the camera, or a pasted bracketed string:

- The field stores the bracketed label, such as `(01)08901234567074(17)271100(10)L2611A(3103)012500(30)12`.
- `fill` writes the GTIN (01), batch (10), expiry (17, as `Y-m-d`; day `00` is the month end), serial (21), net weight (3103, as an exact decimal such as `12.500`) and count (30) into the line. The page clears the fields a label does not carry and shows the product found by GTIN (`Product::forGtin()` matches the stored EAN-13 with the leading zero).
- `require: ['01', '17']`: a label without a GTIN or expiry is rejected. So are bad check digits, impossible dates and plain EAN-13/UPC-A product barcodes, with the package's message under the field.
- Two `validateUsing()` closures run after the GS1 check on the bracketed string: expired stock and GTINs that are not in Products are rejected.
- **Receive line** saves a `ReceivedLine` (the checks run again on submit). The table below redraws each saved label as GS1-128 with `BarcodeColumn`, through the signed image route.

Scan the labels on **GS1 Labels** (`/admin/gs1-labels`) from another screen or a printout: three are received, and four are rejected (bad check digit, printed as Code 128 because GS1-128 refuses to draw it; no expiry; expired; a plain EAN-13). Their dates are relative to today. Set a handheld scanner to send FNC1 as GS (ASCII 29).

### Manufacturing

A work-order station (`app/Filament/Pages/ManufacturingStation.php`) built only on the package's public API:

1. **Operator badge**: `lookupUsing()` against `App\Support\BadgeDirectory`, a plain class standing in for an HR API (no Eloquent). `sticky()`.
2. **Work order**: `lookup(AssemblyOrder::class, 'number', fn ($query) => $query->where('status', 'open'))`. `sticky()`.
3. **Rework reason**: `when()` the order is a rework order (WO-5002).
4. **Components**: `repeat()->finishWith('DONE')`, each label (`CMP-100:0001`) checked by the piped stage `App\Scanning\ComponentBelongsToBom` (on the BOM, not one too many). Duplicate serials are rejected.

Finishing runs `validateAllUsing()` ("BOM incomplete: missing 1 × CMP-200"), then `autoSubmit()` saves an `AssemblyBuild`. `onStepAccepted()` / `onStepRejected()` feed the activity list. The buttons call `resetSequence()`, `goToStep('components')` and send `qr-sequence-pause` / `qr-sequence-resume`.

Styling hooks on show: `extraAttributes(['class' => 'mfg-station'])` with `--fi-qr-*` CSS variables in `resources/css/filament/admin/theme.css`, a `QrCodeRenderHook::SEQUENCE_STEPS_AFTER` legend scoped to `data.station` (`AppServiceProvider`), and a published sub-view, `resources/views/vendor/filament-qr-code/components/sequence/step-chip.blade.php`, that pins sticky steps in every sequence.

Try `OP-001`, `WO-5001`, `CMP-100:0001`, `CMP-200:0001`, `CMP-100:0002`, `DONE`. Print the labels from **QR Labels**.

URLs are kebab-case and route names camelCase (`filament.admin.pages.posCheckout`, `filament.admin.pages.goodsReceiving`, `filament.admin.pages.gs1Receiving`, `filament.admin.pages.gs1Labels`, `filament.admin.pages.manufacturing`, `filament.admin.pages.qrLabels`).

Recipes in the package docs mirror these pages: [`docs/recipes`](https://github.com/mmuqiitf/filament-qr-code/tree/main/docs/recipes).

## Tests

```bash
composer test                       # Pint, PHPStan and Pest
vendor/bin/pest tests/Browser       # Playwright browser tests (npx playwright install chromium first)
```

`tests/Browser/Gs1AccessibilityTest.php` runs axe-core (bundled with `pestphp/pest-plugin-browser`, so no extra dependency) against the GS1 Labels and GS1 Receiving pages through `assertNoAccessibilityIssues(level: 3)`, which reports every impact from critical to minor. A leftover `public/hot` from an old `npm run dev` stops the built CSS from loading, and the contrast checks then judge an unstyled page, so delete it before browser runs.

Browser runs can leave a `playwright run-server` process behind; kill it if your terminal waits after Pest exits.

The nightly `package-main` workflow runs this app against `mmuqiitf/filament-qr-code:dev-main` so package regressions show up before a release.
