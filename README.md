# CPS DLVN
Claim Payment System - DLVN: internal use only.

# Start project using Code Igniter framework and Twig template engine
1. Install Code Igniter 3.1.9: https://codeigniter.com/download
2. Install Composer
3. Install Twig:
  a) Add the following code in composer.json:
  "config": {
    "vendor-dir": "application/vendor"
  },
  "require": {
    "twig/twig": "~2.0"
  }
  b) Run the composer file using: composer.phar install
4. Setup the project:
  a) Enable composer_autoload in the config file (/application/config/config.php)
    $config['composer_autoload'] = TRUE;
  b) Create new config file (/application/config/twig.php)
  c) Create new library (/application/libraries/Twig.php)
  d) Create core file (/application/core/MY_Loader.php) to extend Codeigniter Loader
  e) Add twig.php config file and Twig.php library to your autoload configuration (/application/config/autoload.php)
    $autoload['libraries'] = array('Twig');
    $autoload['config'] = array('twig');

ALTER TABLE `payments` ADD `VCB_CODE` VARCHAR(12) NULL DEFAULT NULL AFTER `VCB_SEQ`;