# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the module uses
[semantic versioning](https://semver.org/).

## [2.1.0] - 2026-10-21

### Added

- A "Load module styles" setting (`M4P_ADDTOCARTFROMFILE_MODULE_CSS`, on by default) for themes that style
  the import button and dialog themselves. The upgrade script adds it with the default value.

### Changed

- Scripts and styles load only on the cart and checkout pages.
- The dialog opens through a delegated click and moves under `body` when opened, so it works with one-page
  checkouts that re-render the cart block.
- The hint under the file field is found by `data-m4p-atcff-hint`, so theme templates can use their own classes.
- A row with an invalid quantity reports the quantity as written in the file.
- pl-PL: "Indeks" for the product reference, impersonal wording in the import help text.

## [2.0.0] - 2026-10-06

First open-source release, published under the MIT license.

### Added

- Combinations are resolved by their own reference or EAN-13, so a catalogue that gives every
  variant its own reference imports per variant.
- Minimum order quantities are checked; rows below them are reported in the result table.
- Products sold on backorder take the full requested quantity.
- The result table downloads as a CSV report.
- A configurable row limit per import, reported back to the customer when exceeded.
- Drag and drop, keyboard dismissal and an accessible dialog in the import modal.
- Complete en-US and pl-PL XLIFF catalogues.

### Changed

- The spreadsheet parser (SheetJS 0.20.3, Apache 2.0) ships with the module and loads lazily on the
  first spreadsheet pick; the module makes no third-party requests.
- The whole file is imported in one request, so stock checks run in file order and the report
  follows the rows of the file.
- Settings moved to upper-case keys and are migrated from the 1.x names on install.
- The import endpoint accepts JSON requests carrying the front-office token and works on the cart
  of the current session.
- The modal was rebuilt without jQuery, fancybox or the bundled Bootstrap.
- Each file format follows its own setting; with spreadsheets off, spreadsheet files are rejected.
- Status messages and row labels come from the translation catalogues.

### Removed

- The bundled copy of Bootstrap (65 kB) and six Sen webfont files (~90 kB), which the module shipped
  but the theme already provides.
- The unused `displayCartImportProd` hook.
- Leftover `ask_product` mail templates belonging to a different module.
- The `translations/pl.php` catalogue of the old translation system.
