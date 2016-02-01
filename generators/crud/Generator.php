<?php

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace claudejanz\mygii\generators\crud;

use claudejanz\mygii\generators\model\Generator as ModelGenerator;
use ReflectionClass;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\TableSchema;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;

/**
 * Generates CRUD
 *
 * @property array $columnNames Model column names. This property is read-only.
 * @property string $controllerID The controller ID (without the module ID prefix). This property is
 * read-only.
 * @property array $searchAttributes Searchable attributes. This property is read-only.
 * @property boolean|TableSchema $tableSchema This property is read-only.
 * @property string $viewPath The controller view path. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Generator extends yii\gii\generators\crud\Generator {

    public $exceptions = 'created_by, created_at, updated_by, updated_at';
    public $exceptionsArray = [];

    /**
     * @var array relations to be excluded in UI rendering
     */
    public $skipRelations = [];

    /**
     * @inheritdoc
     */
    public function getName() {
        return 'CRUD Generator with exceptions';
    }

    /**
     * @inheritdoc
     */
    public function getDescription() {
        return 'This generator generates a controller and views that implement CRUD (Create, Read, Update, Delete)
            operations for the specified data model. I added exeptions to remove autoupdated fields to be in Form';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return array_merge(parent::rules(), [
            [['exceptions'], 'splitArray'],
        ]);
    }

    /**
     * Split params into Array
     * @param type $attribute
     */
    public function splitArray($attribute) {
        $this->exceptionsArray = preg_split('/[\s,]+/', $this->$attribute, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'exceptions' => 'Fields to omit from form',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints() {
        return array_merge(parent::hints(), [
            'exceptions' => 'This fields will be omited from générator. For exemple: created_by, created_at, updated_by, updated_at',
        ]);
    }

   
    /**
     * @inheritdoc
     */
    public function stickyAttributes() 
    {
        return array_merge(parent::stickyAttributes(), [ 'exceptions']);
    }

    
    /**
     * Generates code for active field
     * @param  string $attribute
     * @return string
     */
    public function generateActiveField($attribute) {
        if ($this->template == 'default') {
            $tableSchema = $this->getTableSchema();
            if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
                if (preg_match('/^(password|pass|passwd|passcode)$/i', $attribute)) {
                    return "\$form->field(\$model, '$attribute')->passwordInput()";
                } else {
                    return "\$form->field(\$model, '$attribute')";
                }
            }
            $column = $tableSchema->columns[$attribute];
            if ($column->phpType === 'boolean') {
                return "\$form->field(\$model, '$attribute')->checkbox()";
            } elseif ($column->type === 'text') {
                return "\$form->field(\$model, '$attribute')->textarea(['rows' => 6])";
            } else {
                if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                    $input = 'passwordInput';
                } else {
                    $input = 'textInput';
                }
            if (is_array($column->enumValues) && count($column->enumValues) > 0) {
                $dropDownOptions = [];
                foreach ($column->enumValues as $enumValue) {
                    $dropDownOptions[$enumValue] = Inflector::humanize($enumValue);
                }
                return "\$form->field(\$model, '$attribute')->dropDownList("
                    . preg_replace("/\n\s*/", ' ', VarDumper::export($dropDownOptions)).", ['prompt' => ''])";
            } elseif ($column->phpType !== 'string' || $column->size === null) {
                    return "\$form->field(\$model, '$attribute')->$input()";
                } else {
                return "\$form->field(\$model, '$attribute')->$input(['maxlength' => true])";
                }
            }
        } elseif ($this->template == 'kartik') {
            $model = new $this->modelClass();
            $attributeLabels = $model->attributeLabels();
            $tableSchema = $this->getTableSchema();
            if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
                if (preg_match('/^(password|pass|passwd|passcode)$/i', $attribute)) {
                    return "'$attribute'=>['type'=> TabularForm::INPUT_PASSWORD,'options'=>['placeholder'=>'Enter " . $attributeLabels[$attribute] . "...']],";
                    //return "\$form->field(\$model, '$attribute')->passwordInput()";
                } else {
                    return "'$attribute'=>['type'=> TabularForm::INPUT_TEXT, 'options'=>['placeholder'=>'Enter " . $attributeLabels[$attribute] . "...']],";
                    //return "\$form->field(\$model, '$attribute')";
                }
            }
            $column = $tableSchema->columns[$attribute];
            if ($column->phpType === 'boolean') {
                //return "\$form->field(\$model, '$attribute')->checkbox()";
                return "'$attribute'=>['type'=> Form::INPUT_CHECKBOX, 'options'=>['placeholder'=>'Enter " . $attributeLabels[$attribute] . "...']],";
            } elseif ($column->type === 'text') {
                //return "\$form->field(\$model, '$attribute')->textarea(['rows' => 6])";
                return "'$attribute'=>['type'=> Form::INPUT_WIDGET, 'widgetClass'=>Redactor::classname(),'options'=>[]],";
                return "'$attribute'=>['type'=> Form::INPUT_TEXTAREA, 'options'=>['placeholder'=>'Enter " . $attributeLabels[$attribute] . "...','rows'=> 6]],";
            } elseif ($column->type === 'date') {
                return "'$attribute'=>['type'=> Form::INPUT_WIDGET, 'widgetClass'=>DateControl::classname(),'options'=>['type'=>DateControl::FORMAT_DATE]],";
            } elseif ($column->type === 'time') {
                return "'$attribute'=>['type'=> Form::INPUT_WIDGET, 'widgetClass'=>DateControl::classname(),'options'=>['type'=>DateControl::FORMAT_TIME]],";
            } elseif ($column->type === 'datetime' || $column->type === 'timestamp') {
                return "'$attribute'=>['type'=> Form::INPUT_WIDGET, 'widgetClass'=>DateControl::classname(),'options'=>['type'=>DateControl::FORMAT_DATETIME]],";
            } else {
                if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                    $input = 'INPUT_PASSWORD';
                } else {
                    $input = 'INPUT_TEXT';
                }
                if ($column->phpType !== 'string' || $column->size === null) {
                    //return "\$form->field(\$model, '$attribute')->$input()";
                    return "'$attribute'=>['type'=> Form::" . $input . ", 'options'=>['placeholder'=>'Enter " . $attributeLabels[$attribute] . "...']],";
                } else {
                    //return "\$form->field(\$model, '$attribute')->$input(['maxlength' => $column->size])";
                    return "'$attribute'=>['type'=> Form::" . $input . ", 'options'=>['placeholder'=>'Enter " . $attributeLabels[$attribute] . "...', 'maxlength'=>" . $column->size . "]],";
                }
            }
        }
    }

   

   

    
 

    public function getModelNameAttribute($modelClass) {
        $model = new $modelClass;
        // TODO: cleanup, get-label-methods, move to config
        if ($model->hasMethod('get_label')) {
            return '_label';
        }
        if ($model->hasMethod('getLabel')) {
            return 'label';
        }
        foreach ($modelClass::getTableSchema()->getColumnNames() as $name) {
            switch (strtolower($name)) {
                case 'name':
                case 'title':
                case 'name_id':
                case 'default_title':
                case 'default_name':
                    return $name;
                    break;
                default:
                    continue;
                    break;
            }
        }

        return $modelClass::primaryKey()[0];
    }

    /**
     * Finds relations of a model class
     *
     * return values can be filtered by types 'belongs_to', 'many_many', 'has_many', 'has_one', 'pivot'
     *
     * @param ActiveRecord $modelClass
     * @param array $types
     *
     * @return array
     */
    public function getModelRelations($modelClass, $types = ['belongs_to', 'many_many', 'has_many', 'has_one', 'pivot']) {
        $reflector = new ReflectionClass($modelClass);
        $model = new $modelClass;
        $stack = [];
        $modelGenerator = new ModelGenerator;
        foreach ($reflector->getMethods() AS $method) {
            if (in_array(substr($method->name, 3), $this->skipRelations)) {
                continue;
            }
            // look for getters
            if (substr($method->name, 0, 3) !== 'get') {
                continue;
            }
            // skip class specific getters
            $skipMethods = [
                'getRelation',
                'getBehavior',
                'getFirstError',
                'getAttribute',
                'getAttributeLabel',
                'getOldAttribute'
            ];
            if (in_array($method->name, $skipMethods)) {
                continue;
            }
            // check for relation
            try {
                $relation = @call_user_func(array($model, $method->name));
                if ($relation instanceof ActiveQuery) {
                    #var_dump($relation->primaryModel->primaryKey);
                    if ($relation->multiple === false) {
                        $relationType = 'belongs_to';
                    } elseif ($this->isPivotRelation($relation)) { # TODO: detecttion
                        $relationType = 'pivot';
                    } else {
                        $relationType = 'has_many';
                    }

                    if (in_array($relationType, $types)) {
                        $name = $modelGenerator->generateRelationName([$relation], $model->getTableSchema(), substr($method->name, 3), $relation->multiple);
                        $stack[$name] = $relation;
                    }
                }
            } catch (Exception $e) {
                Yii::error("Error: " . $e->getMessage(), __METHOD__);
            }
        }
        return $stack;
    }

    public function isPivotRelation(ActiveQuery $relation) {
        $model = new $relation->modelClass;
        $table = $model->tableSchema;
        $pk = $table->primaryKey;
        if (count($pk) !== 2) {
            return false;
        }
        $fks = [];
        foreach ($table->foreignKeys as $refs) {
            if (count($refs) === 2) {
                if (isset($refs[$pk[0]])) {
                    $fks[$pk[0]] = [$refs[0], $refs[$pk[0]]];
                } elseif (isset($refs[$pk[1]])) {
                    $fks[$pk[1]] = [$refs[0], $refs[$pk[1]]];
                }
            }
        }
        if (count($fks) === 2 && $fks[$pk[0]][0] !== $fks[$pk[1]][0]) {
            return $fks;
        } else {
            return false;
        }
    }

}
