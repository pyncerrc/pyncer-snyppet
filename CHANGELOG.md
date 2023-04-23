# Change Log

## 1.1.1 - 2023-04-11

### Changed

- Made it so 'app' snyppet is always first when iterating.

## 1.1.0 - 2023-03-22

### Added

- Required snyppet option to specify what snyppets need to be installed before
  the current snyppet can be installed.

### Fixed

- Fixed issue where snyppets would be skipped if more than one was installing
  at one time.
- Fixed related installations not being installed when related snyppet already installed.
- Fixed version comparisons to use version\_compare function.

## 1.0.1 - 2023-03-07

### Changed

- Added snyppets parameter to SnyppetManager constructor to optionally limit
  what snyppets should be used.

## 1.0.0 - 2023-03-07

Initial release.
