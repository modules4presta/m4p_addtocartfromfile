# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the module uses
[semantic versioning](https://semver.org/).

## [2.0.0] - 2026-10-06

First open-source release, published under the MIT license.

### Added

- Combinations are resolved by their own reference or EAN-13, so a B2B catalogue that gives every
  variant a reference no longer fails to import.
- Minimum order quantities are checked and reported instead of silently rejecting the row.
- Products sold on backorder take the full requested quantity rather than being capped at stock.
- The result table downloads as a CSV report.
- A configurable row limit per import, reported back to the customer when exceeded.
- Drag and drop, keyboard dismissal and an accessible dialog in the import modal.
- Complete en-US and pl-PL XLIFF catalogues.

### Changed

- The spreadsheet parser (SheetJS 0.20.3, Apache 2.0) ships with the module and loads lazily on the
  first spreadsheet pick, instead of being fetched from `unpkg.com` on every cart page view.
- The whole file is imported in one request. Previously each row was sent separately, which raced
  against itself on the shop's stock checks and made the report depend on the order replies arrived in.
- Settings moved to upper-case keys and are migrated from the 1.x names on install.
- The import endpoint requires a JSON content type as well as the front-office token, and takes the
  cart from the session. The token alone is the same value for every guest of a shop, so on its own
  it would not have stopped another site from posting rows into a visitor's cart.
- The modal was rebuilt without jQuery, fancybox or the bundled Bootstrap.

### Removed

- The bundled copy of Bootstrap (65 kB) and six Sen webfont files (~90 kB), which the module shipped
  but the theme already provides.
- The `displayCartImportProd` hook, which the module created and registered but nothing ever called.
- Leftover `ask_product` mail templates belonging to a different module.
- The `translations/pl.php` catalogue of the old translation system.

### Fixed

- Each file format is honoured by its own setting, so turning spreadsheets off rejects them.
- Status messages and row labels come from the translation catalogues.
