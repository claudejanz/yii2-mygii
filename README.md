yii2-mygii
==========

My yii2 generators

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
            ],
        ],
    ],
```
