<?php

use yii\gii\generators\crud\Generator;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\View;

/* @var $generator Generator */
/* @var $this View */

echo "<?php\n";
?>

use app\extentions\helpers\MyPjax;
use claudejanz\toolbox\widgets\ajax\AjaxModalButton;
use claudejanz\toolbox\widgets\BootstrapPortlet;
use claudejanz\toolbox\widgets\MySortable;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\web\View;

/**
* @var View $this
*/
Yii::$app->controller->page->title = Yii::t('app', '<?= Inflector::camel2words(StringHelper::basename($generator->modelClass),false) ?>');
echo Html::beginTag('div', ['class' => '<?= $generator->controllerID ?>-order']);
MyPjax::begin([
    'id' => 'order',
]);
$editableButtonOptions = ['class' => 'btn btn-sm btn-default kv-editable-button'];
$buttons = [];


$buttons[] = AjaxModalButton::widget([
    'label'       => Icon::show('plus') . Yii::t('app', 'Add <?= Inflector::camel2words(StringHelper::basename($generator->modelClass),false) ?>'),
    'encodeLabel' => false,
    'url'         => [
        'create',
        'id' => $model->id
    ],
    'title'       => Yii::t('app', 'Add <?= Inflector::camel2words(StringHelper::basename($generator->modelClass),false) ?>'),
    'success'     => '#order',
    'options'     => [
        'title' => Yii::t('app', 'Add <?= Inflector::camel2words(StringHelper::basename($generator->modelClass),false) ?>'),
        'class' => 'btn btn-success',
    ],
]);
// $buttons[] = Html::a(Yii::t('app', 'Back to page'), ['pages/view', 'id' => Yii::$app->request->queryParams['id']], ['class' => 'btn btn-primary', 'data-pjax' => 0]);
BootstrapPortlet::begin(['title' => Yii::t('app', 'Order <?= Inflector::camel2words(StringHelper::basename($generator->modelClass),false) ?>')]);
echo MySortable::widget([
    'options'  => ['id' => '<?= $generator->controllerID ?>-order'],
    'items'    => $models,
    'url'      => ['save-order', 'id' => Yii::$app->request->queryParams['id']],
    'itemView' => '/<?= $generator->controllerID ?>/_order',
]);

echo Html::beginTag('div');
echo join(' ', $buttons);
echo Html::endTag('div');

BootstrapPortlet::end();

MyPjax::end();

echo Html::endTag('div');
