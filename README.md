[![license](https://img.shields.io/github/license/btactic/SuitePY-service.svg?style=flat-square)](LICENSE)
[![GitHub (pre-)release](https://img.shields.io/github/release/btactic/SuitePY-service/all.svg?style=flat-square)](https://github.com/btactic/SuitePY-service/releases/latest)

# SuitePY-service
Custom SuiteCRM WebService for SuitePY to install new custom endpoints for API v8.

# Requirements

Install the _mPDF_ library for PHP 8.2:

```
cd /path/to/suitecrm-root/
php8.2 /usr/bin/composer require mpdf/mpdf:8.2.4
```

Check if the correct version of the mpdf is the 8.2.4, you can do that with:

```
php8.2 /usr/bin/composer show -i | grep 'mpdf'
```

# How to install

For Suitecrm 7.12 or superior versions zip elements must not be all together in a general dir, and must be separated:

```
git clone https://github.com/joelbtactic/SuitePY-service.git
cd SuitePY-service
```

And with the use of the `zip` command you will generate the zip you need:

```
zip -r suitepy-service.zip custom LICENSE manifest.php README.md  
```

Finally, to install this service you must use the **Module Loader** option in your SuiteCRM.

**Not compatible with Suitecrm 8.X.**

## Custom API Endpoints
The new custom endpoints are:

```
{{suitecrm.url}}/Api/V8/custom/getNoteAttach/{note_id}
```
This first endpoint, retrieve an attachment from a note.

```
{{suitecrm.url}}/Api/V8/custom/getPdfTemplate/{bean_module}/{bean_id}/{template_id}
```

And this second endpoint, retrieve PDF Template for a given module record.
