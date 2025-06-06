# TYPO3 Extension `Academic Jobs` (READ-ONLY)

|                  | URL                                                                |
|------------------|--------------------------------------------------------------------|
| **Repository:**  | https://github.com/fgtclb/academic-jobs                            |
| **Read online:** | https://docs.typo3.org/p/fgtclb/academic/academic-jobs/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/academic_jobs/              |

## Description

This TYPO3 extension allows the entry of external job offers by front-end users
in TYPO3 using a form. The job offers can then be checked and published through
an approval process.

Records can be created, edit and displayed in the frontend and there are a list-
and a detail view for the frontend .

> [!NOTE]
> This extension is currently in beta state - please notice that there might be changes to the structure

## Compatibility

| Branch | Version       | TYPO3       | PHP                                     |
|--------|---------------|-------------|-----------------------------------------|
| main   | 2.0.x-dev     | ~v12 + ~v13 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| 2      | ^2, 2.0.x-dev | ~v12 + ~v13 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| 1      | ^1, 1.2.x-dev | v11 + ~v12  | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/academic_jobs/)
* Extension Manager
* composer

We prefer composer installation:

```bash
composer require 'fgtclb/academic-jobs':'^2'
```

> [!IMPORTANT]
> `2.x.x` is still in development and not all academics extension are fully tested in v12 and v13,
> but can be installed in composer instances to use, test them. Testing and reporting are welcome.

**Testing 2.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and installed with:

```shell
composer require \
  'fgtclb/academic-jobs':'2.*.*@dev'
```

## Upgrade from `1.x`

Upgrading from `1.x` to `2.x` includes breaking changes, which needs to be
addressed manualy in case not automatic upgrade path is available. See the
[UPGRADE.md](./UPGRADE.md) file for details.

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
