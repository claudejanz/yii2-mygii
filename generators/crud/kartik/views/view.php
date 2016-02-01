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

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\datecontrol\DateControl;
use dmstr\bootstrap\Tabs;

/**
* @var yii\web\View $this
* @var <?= ltrim($generator->modelClass, '\\') ?> $model
*/

?>
<div class="giiant-crud <?= Inflector::camel2id(StringHelper::basename($generator->modelClass), '-', true) ?>-view">

    <!-- menu buttons -->
    <p class='pull-left'>
        <?= "<?= " ?>Html::a('<span class="glyphicon glyphicon-pencil"></span> ' . <?= $generator->generateString('Edit') ?>, ['update', <?= $urlParams ?>],['class' => 'btn btn-info']) ?>
        <?= "<?= " ?>Html::a('<span class="glyphicon glyphicon-plus"></span> ' . <?= $generator->generateString('New') ?>, ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <p class="pull-right">
        <?= "<?= " ?>Html::a('<span class="glyphicon glyphicon-list"></span> ' . <?= $generator->generateString('List ' . Inflector::pluralize(StringHelper::basename($generator->modelClass))) ?>, ['index'], ['class'=>'btn btn-default']) ?>
    </p>

    <div class="clearfix"></div>

    <!-- flash message -->
    <?= "<?php if (\\Yii::\$app->session->getFlash('deleteError') !== null) : ?>
        <span class=\"alert alert-info alert-dismissible\" role=\"alert\">
            <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">
            <span aria-hidden=\"true\">&times;</span></button>
            <?= \\Yii::\$app->session->getFlash('deleteError') ?>
        </span>
    <?php endif; ?>" ?>


    <div class="panel panel-default">
        <div class="panel-heading">
            <h2>
                <?= "<?= \$model->" . $generator->getModelNameAttribute($generator->modelClass) . " ?>" ?>
            </h2>
        </div>

        <div class="panel-body">
            <div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-view">
                <div class="page-header">
                    <h1><?= "<?= " ?>Html::encode($this->title) ?></h1>
                </div>

                <?php
                echo "<?php \$this->beginBlock('{$generator->modelClass}'); ?>\n";
                ?>

                <?= "<?= " ?>DetailView::widget([
                'model' => $model,
                'condensed'=>false,
                'hover'=>true,
                'mode'=>Yii::$app->request->get('edit')=='t' ? DetailView::MODE_EDIT : DetailView::MODE_VIEW,
                'panel'=>[
                'heading'=>$this->title,
                'type'=>DetailView::TYPE_INFO,
                ],
                'attributes' => [
                <?php
                if (($tableSchema = $generator->getTableSchema()) === false) {
                    foreach ($generator->getColumnNames() as $name) {
                        echo "            '" . $name . "',\n";
                    }
                } else {
                    foreach ($generator->getTableSchema()->columns as $column) {

                        $format = $generator->generateColumnFormat($column);

                        if ($column->type === yii\db\Schema::TYPE_TEXT) {
                            echo "            [
                'attribute'=>'$column->name',
                'format'=>['date',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['date'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['date'] : 'd-m-Y'],
                'type'=>DetailView::INPUT_WIDGET,
                'widgetOptions'=> [
                    'class'=>DateControl::classname(),
                    'type'=>DateControl::FORMAT_DATE
                ]
            ],\n";
                        } elseif ($column->type === yii\db\Schema::TYPE_DATE) {
                            echo "            [
                'attribute'=>'$column->name',
                'format'=>['date',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['date'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['date'] : 'd-m-Y'],
                'type'=>DetailView::INPUT_WIDGET,
                'widgetOptions'=> [
                    'class'=>DateControl::classname(),
                    'type'=>DateControl::FORMAT_DATE
                ]
            ],\n";
                        } elseif ($column->type === yii\db\Schema::TYPE_TIME) {
                            echo "            [
                'attribute'=>'$column->name',
                'format'=>['time',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['time'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['time'] : 'H:i:s A'],
                'type'=>DetailView::INPUT_WIDGET,
                'widgetOptions'=> [
                    'class'=>DateControl::classname(),
                    'type'=>DateControl::FORMAT_TIME
                ]
            ],\n";
                        } elseif ($column->type === yii\db\Schema::TYPE_DATETIME || $column->type === yii\db\Schema::TYPE_TIMESTAMP) {
                            echo "            [
                'attribute'=>'$column->name',
                'format'=>['datetime',(isset(Yii::\$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::\$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A'],
                'type'=>DetailView::INPUT_WIDGET,
                'widgetOptions'=> [
                    'class'=>DateControl::classname(),
                    'type'=>DateControl::FORMAT_DATETIME
                ]
            ],\n";
                        } else {
                            echo "            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
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
                'enableEditMode'=>true,
                ]) ?>
                <?= "<?php \$this->endBlock(); ?>\n\n"; ?>

                <?php
                // get relation info $ prepare add button
                $model = new $generator->modelClass;

                $items = <<<EOS
[
    'label'   => '<b class=""># '.\$model->{$model->primaryKey()[0]}.'</b>',
    'content' => \$this->blocks['{$generator->modelClass}'],
    'active'  => true,
],
EOS;

                foreach ($generator->getModelRelations($generator->modelClass, ['has_many']) as $name => $relation) {

                    echo "\n<?php \$this->beginBlock('$name'); ?>\n";

                    $showAllRecords = false;

                    if ($relation->via !== null) {
                        if (!isset($relation->via->from[0])) {
                            var_dump($relation->via);
                            die();
                        }
                        $pivotName = Inflector::pluralize($generator->getModelByTableName($relation->via->from[0]));
                        $pivotRelation = $model->{'get' . $pivotName}();
                        $pivotPk = key($pivotRelation->link);

                        $addButton = "  <?= Html::a(
            '<span class=\"glyphicon glyphicon-link\"></span> ' . " . $generator->generateString('Attach') . " . ' " .
                                Inflector::singularize(Inflector::camel2words($name)) .
                                "', ['" . $generator->createRelationRoute($pivotRelation, 'create') . "', '" .
                                Inflector::singularize($pivotName) . "'=>['" . key(
                                        $pivotRelation->link
                                ) . "'=>\$model->{$model->primaryKey()[0]}]],
            ['class'=>'btn btn-info btn-xs']
        ) ?>\n";
                    } else {
                        $addButton = '';
                    }

                    // relation list, add, create buttons
                    echo "<div style='position: relative'><div style='position:absolute; right: 0px; top 0px;'>\n";

                    echo "  <?= Html::a(
            '<span class=\"glyphicon glyphicon-list\"></span> ' . " . $generator->generateString('List All') . " . ' " .
                    Inflector::camel2words($name) . "',
            ['" . $generator->createRelationRoute($relation, 'index') . "'],
            ['class'=>'btn text-muted btn-xs']
        ) ?>\n";
                    // TODO: support multiple PKs
                    echo "  <?= Html::a(
            '<span class=\"glyphicon glyphicon-plus\"></span> ' . " . $generator->generateString('New') . " . ' " .
                    Inflector::singularize(Inflector::camel2words($name)) . "',
            ['" . $generator->createRelationRoute($relation, 'create') . "', '" .
                    Inflector::singularize($name) . "' => ['" . key($relation->link) . "' => \$model->" . $model->primaryKey()[0] . "]],
            ['class'=>'btn btn-success btn-xs']
        ); ?>\n";
                    echo $addButton;

                    echo "</div></div>"; #<div class='clearfix'></div>\n";
                    // render pivot grid
                    if ($relation->via !== null) {
                        $pjaxId = "pjax-{$pivotName}";
                        $gridRelation = $pivotRelation;
                        $gridName = $pivotName;
                    } else {
                        $pjaxId = "pjax-{$name}";
                        $gridRelation = $relation;
                        $gridName = $name;
                    }

                    $output = $generator->relationGrid($gridName, $gridRelation, $showAllRecords);

                    // render relation grid
                    if (!empty($output)):
                        echo "<?php Pjax::begin(['id'=>'pjax-{$name}', 'enableReplaceState'=> false, 'linkSelector'=>'#pjax-{$name} ul.pagination a, th a', 'clientOptions' => ['pjax:success'=>'function(){alert(\"yo\")}']]) ?>\n";
                        echo "<?= " . $output . "?>\n";
                        echo "<?php Pjax::end() ?>\n";
                    endif;

                    echo "<?php \$this->endBlock() ?>\n\n";

                    // build tab items
                    $label = Inflector::camel2words($name);
                    $items .= <<<EOS
[
    'content' => \$this->blocks['$name'],
    'label'   => '<small>$label <span class="badge badge-default">'.count(\$model->get{$name}()->asArray()->all()).'</span></small>',
    'active'  => false,
],
EOS;
                }
                ?>

                <?=
                // render tabs
                "<?= Tabs::widget(
                 [
                     'id' => 'relation-tabs',
                     'encodeLabels' => false,
                     'items' => [ $items ]
                 ]
    );
    ?>";
                ?>
            </div>
        </div>
    </div>
</div>