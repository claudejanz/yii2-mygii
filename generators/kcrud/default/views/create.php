<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * @var yii\web\View $this
 * @var yii\gii\generators\crud\Generator $generator
 */

echo "<?php\n";
?>

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

echo Html::beginTag('div',['class'=>'<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-create']);
echo $this->render('_form', [
    'model' => $model,
]);
echo Html::endTag('div');
