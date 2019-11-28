# Eleanorsoft_DiCompile
Speed up Magento2 DI compilation

This module caches file lists during compilation to avoid expensive filesystem scans. Speed improvement is about 40% to 50%.

## Installation
* `composer require eleanorsoft/module-di-compile`
* `php bin\magento setup:upgrade`
* `php bin\magento setup:di:compile`

After compilation, you should see a folder `var\di_cache` with 4 (or more) files. This is the actual cache. From now your compilation process will use these cached lists instead of scanning the whole filesystem.

**Note 1:** these caches contain only core files. All custom modules will be scanned each time as usual.

**Note 2:** if you upgrade Magento 2, delete these files because core modules could change during the upgrade.

See details in the article ["Speed Up Magento 2 DI Compilation (“setup:di:compile”)"](https://www.eleanorsoft.com/speed-up-magento-2-di-compilation-setupdicompile/)
