Freeproduct
===========

An extension that allows configuring sales rules to add free products to cart. This is the Magento 2 version of the same extension that can be found [here](https://github.com/code4business/freeproduct). 
The development and the function of the original Magento1 extension is described in the following two websites:
- <http://www.code4business.de/make-a-gift-magento-warenkorbpreisregeln-um-geschenke-erweitern/>
- <http://www.webguys.de/magento/turchen-21-kostenlose-produkte-uber-warenkorb-preisregeln/>

Requirements
-------
- PHP >= 7.1
- Magento >= 2.2

Supported Product Types
-------
The extension only supports simple and virtual product types, other types or custom options are not supported. The reason is that other product types need additional information that can only be added with IDs. This leads to a way more complicated module; we want to keep this module clean and easy.

Instalation
-------
### Via composer (recommended)
Go to the Magento 2 root directory and run the following commands in the shell:
```
composer require code4business/freeproduct2
bin/magento module:enable C4B_FreeProduct
bin/magento setup:upgrade
```

### Manually
Create the directory `app/code/C4B/FreeProduct` and copy the all the files from this repository into it. Then run:
```
bin/magento module:enable C4B_FreeProduct
bin/magento setup:upgrade
```
Configuration
-------
Sales rules for carts are configured in _Marketing->Cart Price Rules_:  
- In the Actions tab, the Apply field should be set to Add a Gift
- Gift SKU: Product that will be added. Only simple and virtual products without (required) custom options are supported. Multiple comma-separated SKUs can be specified
- Discount Amount: The qty of added gifts  
- The gift item is added once for the whole cart

Action **Add a Gift (for each cart item)** works similarly but will add the gift item for each product in cart. The qty of said product is also taken into consideration.  

This action usually needs conditions to match only specific items *(Apply the rule only to cart items matching the following conditions)*.   

Limitations:
-------
- Gift products are added during discount total processing, after subtotal and shipping totals. Because of that gift products will not be included in any shipping calculations.
- Only simple and virtual products without required custom options are supported.

Current localizations:
-------
- de_DE
- es_ES
- fr_FR
- nl_NL
- pt_PT
- sl_SI

License
-------
[Open Software Licence 3.0 (OSL-3.0)](http://opensource.org/licenses/osl-3.0.php)