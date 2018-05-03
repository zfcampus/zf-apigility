# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.4.0 - 2018-05-03

### Added

- [#200](https://github.com/zfcampus/zf-apigility/pull/200) adds support for PHP 7.1 and 7.2.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#200](https://github.com/zfcampus/zf-apigility/pull/200) removes support for HHVM.

### Fixed

- Nothing.

## 1.3.0 - 2016-07-28

### Added

- [#169](https://github.com/zfcampus/zf-apigility/pull/169) adds support for
  version 3 releases of Zend Framework components, retaining compatibility with
  version 2 releases.
- [#169](https://github.com/zfcampus/zf-apigility/pull/169) adds support in
  `ZF\Apigility\Application` for handling PHP 7 `Throwable`s (in addition to
  standard exceptions).

### Deprecated

- Nothing.

### Removed

- [#169](https://github.com/zfcampus/zf-apigility/pull/169) removes support for
  PHP 5.5.
- [#169](https://github.com/zfcampus/zf-apigility/pull/169) removes the
  dependency on rwoverdijk/assetmanager. It now *suggests* one or the other of:
  - rwoverdijk/assetmanager `^1.7` (not yet released)
  - zfcampus/zf-asset-manager `^1.0`

### Fixed

- Nothing.
