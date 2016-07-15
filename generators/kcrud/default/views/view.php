<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * @var yii\web\View $this
 * @var yii\gii\generators\crud\Generator $generator
 */
$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use kartik\detail\DetailView;
use kartik\datecontrol\DateControl;
use yii\helpers\Html;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

echo Html::beginTag('div',['class'=>'<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-view']);

echo DetailView::widget([
    'model' => $model,
    'condensed'=>false,
    'hover'=>true,
//    'mode'=>Yii::$app->request->get('edit')=='t' ? DetailView::MODE_EDIT : DetailView::MODE_VIEW,
//    'enableEditMode'=>true,
    'panel'=>[
        'heading'=>$this->title,
        'type'=>DetailView::TYPE_INFO,
    ],
    'attributes' => [
<?php
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        echo "      '" . $name . "',\n";
    }
} else {
    foreach ($generator->getTableSchema()->columns as $column) {

        $format = $generator->generateColumnFormat($column);

        switch ($column->type) {
            case 'date':
                echo "      [
            'attribute'=>'$column->name',
            'format'=>['date',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['date'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['date'] : 'd-m-Y'],
            'type'=>DetailView::INPUT_WIDGET,
            'widgetOptions'=> [
                'class'=>DateControl::classname(),
                'type'=>DateControl::FORMAT_DATE
            ]
        ],\n";
                break;
            case 'time':
                echo "      [
            'attribute'=>'$column->name',
            'format'=>['time',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['time'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['time'] : 'H:i:s A'],
            'type'=>DetailView::INPUT_WIDGET,
            'widgetOptions'=> [
                'class'=>DateControl::classname(),
                'type'=>DateControl::FORMAT_TIME
            ]
        ],\n";
                break;
            case 'datetime':
            case 'timestamp':
                echo "      [
            'attribute'=>'$column->name',
            'format'=>['datetime',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A'],
            'type'=>DetailView::INPUT_WIDGET,
            'widgetOptions'=> [
                'class'=>DateControl::classname(),
                'type'=>DateControl::FORMAT_DATETIME
            ]
        ],\n";
                break;
            default:
                echo "      '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
        }
    }
}
?>
    ],
    'deleteOptions'=>[
        'url'=>['delete', 'id' => $model-><?= $generator->getTableSchema()->primaryKey[0] ?>],
        'data'=>[
            'confirm'=>Yii::t('app', 'Are you sure you want to delete this item?'),
            'method'=>'post',
        ],
    ],
]);

echo Html::endTag('div');

