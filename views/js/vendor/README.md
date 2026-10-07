# Vendored dependency

`xlsx.full.min.js` is SheetJS Community Edition 0.20.3, taken unmodified from
<https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js>.

It is licensed under Apache License 2.0 — see `LICENSE-sheetjs.txt`. The file is
shipped with the module instead of being loaded from a CDN, so the shop makes no
third-party requests and the parser cannot change under your feet.

It is only used to turn `.xls` / `.xlsx` uploads into rows, and the module loads
it lazily — the cart page does not download it until a visitor picks a
spreadsheet. Shops that accept CSV only never fetch it at all.
