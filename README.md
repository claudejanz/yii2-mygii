yii2-double-model-gii
=====================

[![Latest Stable Version](https://poser.pugx.org/claudejanz/yii2-mygii/v/stable.svg)](https://packagist.org/packages/claudejanz/yii2-mygii) [![Total Downloads](https://poser.pugx.org/claudejanz/yii2-mygii/downloads.svg)](https://packagist.org/packages/claudejanz/yii2-mygii) [![Latest Unstable Version](https://poser.pugx.org/claudejanz/yii2-mygii/v/unstable.svg)](https://packagist.org/packages/claudejanz/yii2-mygii) [![License](https://poser.pugx.org/claudejanz/yii2-mygii/license.svg)](https://packagist.org/packages/claudejanz/yii2-mygii)


This generator generates two ActiveRecord class for the specified database table. An empty one you can extend and a Base one which is the same as the original model generatior.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
$ php composer.phar require "claudejanz/yii2-mygii": "dev-master"
```

or add

```
"claudejanz/yii2-mygii": "dev-master"
```

to the ```require``` section of your `composer.json` file.

## Usage

```php
//if your gii modules configuration looks like below:
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';

//remove this two lines
```

```php
//Add this into common/config/main-local.php
    'bootstrap' => 'gii',
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
            'generators' => [
                'doubleModel' => [
                    'class' => 'claudejanz\mygii\generators\model\Generator',
                ],
                'kartik-crud' => [
                    'class'     => 'claudejanz\mygii\generators\kcrud\Generator',
                ],
            ],
        ],
    ],
```
