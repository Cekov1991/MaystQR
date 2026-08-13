# Account-level entitlement replaces per-QR-code expiry

MaystQR originally gave every Dynamic QR Code its own 7-day life (`qr_codes.expires_at`, stamped in `QrCode::boot()`) which the Owner extended by buying a one-off `QrCodePackage` for that single code. We replaced it with a single account-level Entitlement: a Dynamic QR Code resolves if and only if its Owner's Trial or Subscription covers today, regardless of when that code was created.

The per-code model priced the wrong thing. An Owner with ten codes faced ten separate expiry dates and ten separate purchases, which is both a worse product and a harder thing to explain than one yearly subscription. It also meant two independent gates could disagree about whether a scan should work.

## Consequences

- `qr_codes.expires_at` still exists but is no longer read by any gate. It is inert, not authoritative — do not reintroduce it as a condition.
- The `QrCodePackage` / `QrCodePackagePurchase` product and its PayPal integration were deleted rather than left dormant, so there is exactly one billing concept in the codebase.
- Quotas (5 Dynamic, 50 Static) gate *creation only*. They never stop an existing code from resolving, because codes are printed and a rule introduced today must not break an asset produced yesterday.
