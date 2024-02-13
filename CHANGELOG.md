# Change Log

## 1.3.0 - Unreleased

### Added

- Added initializeMiddleware function to Snyppet class to allow for the overriding of specific middleware initializations.
- Added getExtra function to Snyppet and Snyppet interface.
- Added ability to specify a sub directory for snyppet installs so that multiple snyppets can share a namespace.
- Updated comments for PHPStan validation.

### Fixed
- Fixed issue with version sorting during upgrade process.

## 1.2.0 - 2023-10-19

### Added

- Added required snyppet option to specify snyppet middlewares should run first.
- Added version parameter to SnyppetManager has function to ensure minimum versions.

## 1.1.1 - 2023-04-11

### Changed

- Made it so 'app' snyppet is always first when iterating.

## 1.1.0 - 2023-03-22

### Added

- Added required snyppet option to specify what snyppets need to be installed before the current snyppet can be installed.

### Fixed

- Fixed issue where snyppets would be skipped if more than one was installing at one time.
- Fixed related installations not being installed when related snyppet already installed.
- Fixed version comparisons to use version\_compare function.

## 1.0.1 - 2023-03-07

### Changed

- Added snyppets parameter to SnyppetManager constructor to optionally limit what snyppets should be used.

## 1.0.0 - 2023-03-07

Initial release.
