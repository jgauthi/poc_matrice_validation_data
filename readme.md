# POC Matrice Validation Data
Tool used to process an array of data with respect to a matrix:
* Verification of values against a type, mandatory field or not
* Export of this data to a custom array format
* -> Which dispenses with having common column names between the input and output data

## Prerequisite

* PHP 5.6+ (v1) ou 7.4 (v2)

## Install
`composer install`

Or you can add this poc like a dependency, in this case edit your [composer.json](https://getcomposer.org) (launch `composer update` after edit):
```json
{
  "repositories": [
    { "type": "git", "url": "git@github.com:jgauthi/poc_matrice_validation_data.git" }
  ],
  "require": {
    "jgauthi/poc_matrice_validation_data": "2.*"
  }
}
```

## Usage
You can test with php internal server and go to url http://localhost:8000 :

```shell script
php -S localhost:8000 -t public
```

## Documentation
You can look at [folder public](https://github.com/jgauthi/poc_matrice_validation_data/tree/master/public).

