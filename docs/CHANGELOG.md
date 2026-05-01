# Changelog

All notable changes to the Darejer package are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project loosely tracks [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> **Rule:** every PR that touches package source or public PHP API adds an entry under `Unreleased` using one of: **Added / Changed / Deprecated / Removed / Fixed / Security**. See [`CONTRIBUTING.md`](CONTRIBUTING.md).

---

## [Unreleased]

### Added
- Initial documentation suite under `/docs`. One file per component, action, and architectural concern. See [`docs/README.md`](README.md).

### Changed
- _(nothing yet)_

### Deprecated
- _(nothing yet)_

### Removed
- _(nothing yet)_

### Fixed
- _(nothing yet)_

### Security
- _(nothing yet)_

---

## How to add an entry

1. Add a bullet under the appropriate `Unreleased` heading.
2. Reference the doc page when the change is component-facing — e.g. `Added \`Combobox::createForm()\` — see [components/combobox.md](components/combobox.md).`
3. Cut a release: rename `Unreleased` to `[x.y.z] - YYYY-MM-DD`, then re-add empty `Unreleased` headings on top.
