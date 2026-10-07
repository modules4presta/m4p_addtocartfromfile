# Add to cart from a file for PrestaShop 8 & 9

**Your wholesale customers already keep their order in a spreadsheet — this module lets them upload it instead of retyping it into your catalogue, one search box at a time.**

> **Meta description (155 chars):** Let PrestaShop customers fill the cart from a CSV or XLSX file of references and quantities. Stock-aware, with a per-row report. Free, MIT licensed.

---

## Why retyping an order matters for your store

A B2B order is rarely a browsing session. The buyer works from a stock list, a printout from their own ERP or a spreadsheet a colleague sent them, and they know exactly which references they need and how many. On a normal shop they then have to search for each one, open it, set the quantity and add it — forty times over. The catalogue is not the bottleneck; the typing is.

Two things follow from that. Buyers with long lists put the order off, because it is a chore rather than a purchase, and when they do sit down to it they make mistakes — a transposed digit in a reference, a quantity in the wrong row. Those mistakes do not stay in the cart: they come back as a complaint, a return and a credit note, and someone on your side has to handle all three.

The fix is to accept the file the buyer already has.

- **The order takes a minute instead of half an hour** — the length of the list stops mattering.
- **No transcription mistakes** — the references come from the buyer's own system, not from their memory.
- **Nothing silently goes wrong** — every row is reported back, so the buyer sees what went into the cart before they pay.

## What the module does

A button appears under the cart. The customer picks a CSV or spreadsheet file, the module reads it in the browser, sends the rows to your shop in one request and reports the result row by row: what was added, what was reduced to the available stock and what could not be matched. It writes to the cart and nothing else — no new tables, no core overrides, no changes to your products, prices or stock.

### Key features

- **CSV and spreadsheets** — `.csv`, `.xls` and `.xlsx`, each format switchable on its own.
- **Reference or EAN-13** — a row matches on the reference first and falls back to the EAN-13, so either column alone is enough.
- **Combinations included** — a combination's own reference resolves to that combination, which is what a B2B catalogue usually needs.
- **Stock-aware** — a quantity above what is in stock is reduced to what is available and flagged, instead of the row being dropped; products you sell on backorder are not capped at all.
- **Minimum quantities respected** — rows below a product's minimum order quantity are reported, not forced into the cart.
- **A report the buyer can keep** — the result table downloads as a CSV, which is what they will send you if something looks wrong.
- **A row limit you control** — rows beyond the limit are skipped and reported, so one oversized file cannot tie up your shop.

### What happens when things are not ideal

The spreadsheet parser is a 900 kB JavaScript file, so the module does not load it with the cart page — it is fetched the first time a visitor actually picks an `.xls` or `.xlsx` file. A shop that accepts CSV only never downloads it. The parser ships inside the module rather than coming from a CDN, so your shop makes no third-party requests and the code cannot change without you updating the module.

Turning the module off hides the button and changes nothing else; uninstalling it deletes its four settings and leaves every cart as it is. Because it only ever calls the same cart API that your "add to cart" buttons call, your stock rules, specific prices and cart rules apply to an imported row exactly as they would to a clicked one.

## Compatibility

| | |
|---|---|
| PrestaShop | 1.7.6 – 9.x |
| PHP | 7.2+ |
| Requirements | none — no external service, no PHP extension beyond the PrestaShop defaults |
| Multistore | Compatible — products are resolved per shop |
| Themes | Works with any theme — no template overrides required |

The module overrides no core class and creates no database table. It stores four settings in `ps_configuration` and removes them when uninstalled.

## Installation

1. Upload and install the module from **Modules → Module Manager**.
2. Open its configuration and decide which file formats you accept and how many rows one import may carry.
3. Open the cart page as a customer — the **Fill the cart from a file** button sits under the cart summary.

If the button does not appear, check that the module is enabled in its own configuration as well as installed, and that at least one file format is switched on. With both formats off there is nothing a customer could upload, so the button stays hidden.

## Configuration options

| Setting | Description |
|---|---|
| **Show the import button** | Turns the feature on and off without uninstalling it. |
| **Accept CSV files** | Semicolon-separated files. Leave this on — it is the format every ERP can export. |
| **Accept XLS and XLSX files** | Spreadsheets. Turn it off if you would rather your shop never served the 900 kB parser. |
| **Rows per import** | How many rows one file may carry. The default of 500 suits most wholesale orders; rows above the limit are skipped and reported to the customer. |

## Frequently asked questions

**What should the file look like?**
Three columns in this order: reference, EAN-13, quantity, separated by semicolons. The first row is skipped as a header. One of the two identifying columns is enough — leave the other empty. An example file is linked in the import dialog.

**What happens if a customer orders more than you have in stock?**
The quantity is reduced to what is actually available and the row is marked as reduced, so the buyer sees it before paying. If the product is set to accept backorders, the full quantity goes in untouched.

**Can a customer use this to read anything out of my shop?**
No. The endpoint takes a reference or an EAN and answers only with the quantities for the rows that were sent — it never lists products, and it writes to the session's own cart, with no way to name someone else's. It also requires a JSON content type and the front-office token, so another site cannot post to it on your customer's behalf.

**Is the import reversible?**
Yes — the rows are ordinary cart lines. The customer can change or remove any of them in the cart as usual, and nothing is committed until they place the order.

---

**Keywords:** prestashop csv order import, prestashop b2b quick order, add to cart from file, prestashop wholesale cart, bulk add to cart prestashop, prestashop xlsx import, order by reference

## License

MIT — see [LICENSE](LICENSE). Free to use commercially, fork and modify; keep the copyright notice.

The bundled spreadsheet parser is SheetJS Community Edition, licensed under Apache 2.0 — see
[views/js/vendor/README.md](views/js/vendor/README.md).

## Contributing

Bug reports and pull requests are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). For security
issues, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.

---

Built by [Nice Code](https://nice-code.com/pl/produkty/sklep-b2b-prestashop) — we build and maintain PrestaShop stores.

© Nice Code sp. z o.o. (Modules4Presta) — released under the MIT license.
