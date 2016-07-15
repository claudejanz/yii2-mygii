<?php

use app\gii\kcrud\Generator;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\View;


/**
 * @var View $this
 */
/* @var $generator Generator */

/** @var ActiveRecord $model */
$model = new $generator->modelClass;
$safeAttributes = $model->safeAttributes();
if (empty($safeAttributes)) {
    $safeAttributes = $model->attributes();
}

echo "<?php\n";

?>

use claudejanz\toolbox\widgets\ajax\AjaxSubmit;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;
use yii\helpers\Html;



/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */
/* @var $form yii\widgets\ActiveForm */ 

echo Html::beginTag('div',['class'=>'<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-form']);

$form = ActiveForm::begin(['type'=>ActiveForm::TYPE_VERTICAL]); 
$labels = $model->attributeLabels();
echo Form::widget([
    'model' => $model,
    'form' => $form,
    'columns' => 1,
    'attributes' => [
<?php foreach ($safeAttributes as $attribute): ?>
        <?= $generator->generateActiveField($attribute) . "\n"; ?>
<?php endforeach; ?>
    ]

]);

if (Yii::$app->request->isAjax) {
    echo AjaxSubmit::widget(['label' => $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
        'options' => [
            'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary'
    ]]);
} else {
    echo Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']);
}
ActiveForm::end(); 
echo Html::endTag('div');

