[![license](https://img.shields.io/github/license/sanchezfauste/SuitePY-service.svg?style=flat-square)](LICENSE)
[![GitHub (pre-)release](https://img.shields.io/github/release/sanchezfauste/SuitePY-service/all.svg?style=flat-square)](https://github.com/joelbtactic/SuitePY-service/releases/latest)

# SuitePY-service

Custom SuiteCRM WebService for SuitePY.

# Requirements

Install the _mPDF_ library:

```
composer require mpdf/mpdf:6.1.0
```

> **WARNING: This command will show an error like this:**
>
> In process.php line 344:
>
> proc_open(): fork failed - Cannot allocate memory

Ignore this error and try again using the next command, check if the correct version of the mpdf is the 6.1.0.

```
composer update
```

# How to install

Download zip of [latest release](https://github.com/joelbtactic/SuitePY-service/releases/latest) and install it using Module Loader. For Suitecrm 7.12 or superior versions zip elements must not be all together in a general dir, and must be separated.

It is not compatible for SuiteCRM 8.X versions.

## Custom API EntryPoints

REST URL

```
https://crm.example.com/custom/service/suitepy/rest.php
```

SOAP WSDL

```
https://crm.example.com/custom/service/suitepy/soap.php?wsdl
```
