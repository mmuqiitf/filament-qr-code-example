# filament-qr-code example app

A Filament v5 panel that shows [mmuqiitf/filament-qr-code](https://github.com/mmuqiitf/filament-qr-code) (`^0.5`) in realistic flows.

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
| Interactive QR Customizer | `/admin/qr-customizer` | Generating and styling QR codes |

### POS Checkout

`QrScanner::mode(ScanMode::Continuous)` with `hardwareInput(scope: HardwareInputScope::Page)`: shoot barcodes anywhere on the page without clicking the field. Each accepted scan runs a `Product` lookup and adds a cart line; scanning the same SKU again bumps its quantity (the page merges lines in `addToCart()`). Try `PRD-1001` twice and `PRD-9999` (unknown SKU). The options toggle `continuousDisplay()`, `pauseOnReject()` and `keepCameraOpen()`.

### Goods Receiving

A `QrScanSequence` built from `ScanStep`s, driven by a handheld scanner without focusing anything:

1. **Purchase order**: `sticky()`, so it stays for every item on the PO. Use **Clear** to change it.
2. **Lot**: `optional()`. Without a lot label, scan the SKU straight away and the lot step is skipped.
3. **SKU**: `lookup(Product::class, 'sku')`.
4. **Serial numbers**: `repeat(n)`, one per unit ("Units per item"), with duplicates rejected and a remove button per code.

Once the item is complete, `validateAllUsing()` checks that every serial belongs to the SKU (`SN-PRD-1001-…`) and jumps back to the serials if not. With "Receive automatically" on, `autoSubmit()` calls `submit()` and `resetAfterSubmit()` clears everything but the PO; with it off, press **Receive item** (a normal submit re-runs every check). Try `PO-7001`, `LOT-A1`, `PRD-1001`, `SN-PRD-1001-0001`, `SN-PRD-1001-0002`.

URLs are kebab-case and route names camelCase (`filament.admin.pages.posCheckout`, `filament.admin.pages.goodsReceiving`).

## Tests

```bash
composer test                       # Pint, PHPStan and Pest
vendor/bin/pest tests/Browser       # Playwright browser tests (npx playwright install chromium first)
```

Browser runs can leave a `playwright run-server` process behind; kill it if your terminal waits after Pest exits.

The nightly `package-main` workflow runs this app against `mmuqiitf/filament-qr-code:dev-main` so package regressions show up before a release.
