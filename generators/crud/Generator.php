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
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\ColumnSchema;
use yii\db\Exception;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\web\Controller;

/**
 * Generates CRUD
 *
 * @property array $columnNames Model column names. This property is read-only.
 * @property string $controllerID The controller ID (without the module ID prefix). This property is
 * read-only.
 * @property array $searchAttributes Searchable attributes. This property is read-only.
 * @property boolean|TableSchema $tableSchema This property is read-only.
 * @property string $viewPath The action view file path. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Generator extends \yii\gii\generators\model\Generator {

    public $modelClass;
    public $moduleID;
    public $controllerClass;
    public $baseControllerClass = 'yii\web\Controller';
    public $indexWidgetType = 'grid';
    public $searchModelClass;
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
            [['moduleID', 'controllerClass', 'modelClass', 'searchModelClass', 'baseControllerClass'], 'filter', 'filter' => 'trim'],
            [['modelClass', 'searchModelClass', 'controllerClass', 'baseControllerClass', 'indexWidgetType'], 'required'],
            [['searchModelClass'], 'compare', 'compareAttribute' => 'modelClass', 'operator' => '!==', 'message' => 'Search Model Class must not be equal to Model Class.'],
            [['modelClass', 'controllerClass', 'baseControllerClass', 'searchModelClass'], 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['modelClass'], 'validateClass', 'params' => ['extends' => BaseActiveRecord::className()]],
            [['baseControllerClass'], 'validateClass', 'params' => ['extends' => Controller::className()]],
            [['controllerClass'], 'match', 'pattern' => '/Controller$/', 'message' => 'Controller class name must be suffixed with "Controller".'],
            [['controllerClass', 'searchModelClass'], 'validateNewClass'],
            [['indexWidgetType'], 'in', 'range' => ['grid', 'list']],
            [['modelClass'], 'validateModelClass'],
            [['moduleID'], 'validateModuleID'],
            [['enableI18N'], 'boolean'],
            [['exceptions'], 'splitArray'],
            [['messageCategory'], 'validateMessageCategory', 'skipOnEmpty' => false],
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
    public function attributeLabels() {
        return array_merge(parent::attributeLabels(), [
            'modelClass' => 'Model Class',
            'moduleID' => 'Module ID',
            'controllerClass' => 'Controller Class',
            'baseControllerClass' => 'Base Controller Class',
            'indexWidgetType' => 'Widget Used in Index Page',
            'searchModelClass' => 'Search Model Class',
            'exceptions' => 'Fields to omit from form',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints() {
        return array_merge(parent::hints(), [
            'modelClass' => 'This is the ActiveRecord class associated with the table that CRUD will be built upon.
                You should provide a fully qualified class name, e.g., <code>app\models\Post</code>.',
            'controllerClass' => 'This is the name of the controller class to be generated. You should
                provide a fully qualified namespaced class, .e.g, <code>app\controllers\PostController</code>.',
            'baseControllerClass' => 'This is the class that the new CRUD controller class will extend from.
                You should provide a fully qualified class name, e.g., <code>yii\web\Controller</code>.',
            'moduleID' => 'This is the ID of the module that the generated controller will belong to.
                If not set, it means the controller will belong to the application.',
            'indexWidgetType' => 'This is the widget type to be used in the index page to display list of the models.
                You may choose either <code>GridView</code> or <code>ListView</code>',
            'searchModelClass' => 'This is the name of the search model class to be generated. You should provide a fully
                qualified namespaced class name, e.g., <code>app\models\search\PostSearch</code>.',
            'exceptions' => 'This fields will be omited from générator. For exemple: created_by, created_at, updated_by, updated_at',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function requiredTemplates() {
        return ['controller.php'];
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes() {
        return array_merge(parent::stickyAttributes(), ['baseControllerClass', 'moduleID', 'indexWidgetType', 'exceptions']);
    }

    /**
     * Checks if model class is valid
     */
    public function validateModelClass() {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pk = $class::primaryKey();
        if (empty($pk)) {
            $this->addError('modelClass', "The table associated with $class must have primary key(s).");
        }
    }

    /**
     * Checks if model ID is valid
     */
    public function validateModuleID() {
        if (!empty($this->moduleID)) {
            $module = Yii::$app->getModule($this->moduleID);
            if ($module === null) {
                $this->addError('moduleID', "Module '{$this->moduleID}' does not exist.");
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function generate() {
        $controllerFile = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->controllerClass, '\\')) . '.php');
        $searchModel = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->searchModelClass, '\\') . '.php'));
        $files = [
            new CodeFile($controllerFile, $this->render('controller.php')),
            new CodeFile($searchModel, $this->render('search.php')),
        ];

        $viewPath = $this->getViewPath();
        $templatePath = $this->getTemplatePath() . '/views';
        foreach (scandir($templatePath) as $file) {
            if (is_file($templatePath . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $files[] = new CodeFile("$viewPath/$file", $this->render("views/$file"));
            }
        }

        return $files;
    }

    /**
     * @return string the controller ID (without the module ID prefix)
     */
    public function getControllerID() {
        $pos = strrpos($this->controllerClass, '\\');
        $class = substr(substr($this->controllerClass, $pos + 1), 0, -10);

        return Inflector::camel2id($class);
    }

    /**
     * @return string the action view file path
     */
    public function getViewPath() {
        $module = empty($this->moduleID) ? Yii::$app : Yii::$app->getModule($this->moduleID);

        return $module->getViewPath() . '/' . $this->getControllerID();
    }

    public function getNameAttribute() {
        foreach ($this->getColumnNames() as $name) {
            if (!strcasecmp($name, 'name') || !strcasecmp($name, 'title')) {
                return $name;
            }
        }
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pk = $class::primaryKey();

        return $pk[0];
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
                if ($column->phpType !== 'string' || $column->size === null) {
                    return "\$form->field(\$model, '$attribute')->$input()";
                } else {
                    return "\$form->field(\$model, '$attribute')->$input(['maxlength' => $column->size])";
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

    /**
     * Generates code for active search field
     * @param  string $attribute
     * @return string
     */
    public function generateActiveSearchField($attribute) {
        $tableSchema = $this->getTableSchema();
        if ($tableSchema === false) {
            return "\$form->field(\$model, '$attribute')";
        }
        $column = $tableSchema->columns[$attribute];
        if ($column->phpType === 'boolean') {
            return "\$form->field(\$model, '$attribute')->checkbox()";
        } else {
            return "\$form->field(\$model, '$attribute')";
        }
    }

    /**
     * Generates column format
     * @param  \yii\db\ColumnSchema $column
     * @return string
     */
    public function generateColumnFormat($column) {
        if ($column->phpType === 'boolean') {
            return 'boolean';
        } elseif ($column->type === 'text') {
            return 'ntext';
        } elseif (stripos($column->name, 'time') !== false && $column->phpType === 'integer') {
            return 'datetime';
        } elseif (stripos($column->name, 'email') !== false) {
            return 'email';
        } elseif (stripos($column->name, 'url') !== false) {
            return 'url';
        } else {
            return 'text';
        }
    }

    /**
     * Generates validation rules for the search model.
     * @return array the generated validation rules
     */
    public function generateSearchRules() {
        if (($table = $this->getTableSchema()) === false) {
            return ["[['" . implode("', '", $this->getColumnNames()) . "'], 'safe']"];
        }
        $types = [];
        foreach ($table->columns as $column) {
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                default:
                    $types['safe'][] = $column->name;
                    break;
            }
        }

        $rules = [];
        foreach ($types as $type => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
        }

        return $rules;
    }

    /**
     * @return array searchable attributes
     */
    public function getSearchAttributes() {
        return $this->getColumnNames();
    }

    /**
     * Generates the attribute labels for the search model.
     * @return array the generated attribute labels (name => label)
     */
    public function generateSearchLabels() {
        /** @var Model $model */
        $model = new $this->modelClass();
        $attributeLabels = $model->attributeLabels();
        $labels = [];
        foreach ($this->getColumnNames() as $name) {
            if (isset($attributeLabels[$name])) {
                $labels[$name] = $attributeLabels[$name];
            } else {
                if (!strcasecmp($name, 'id')) {
                    $labels[$name] = 'ID';
                } else {
                    $label = Inflector::camel2words($name);
                    if (strcasecmp(substr($label, -3), ' id') === 0) {
                        $label = substr($label, 0, -3) . ' ID';
                    }
                    $labels[$name] = $label;
                }
            }
        }

        return $labels;
    }

    /**
     * Generates search conditions
     * @return array
     */
    public function generateSearchConditions() {
        $columns = [];
        if (($table = $this->getTableSchema()) === false) {
            $class = $this->modelClass;
            /** @var Model $model */
            $model = new $class();
            foreach ($model->attributes() as $attribute) {
                $columns[$attribute] = 'unknown';
            }
        } else {
            foreach ($table->columns as $column) {
                $columns[$column->name] = $column->type;
            }
        }

        $likeConditions = [];
        $hashConditions = [];
        foreach ($columns as $column => $type) {
            switch ($type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_BOOLEAN:
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    $hashConditions[] = "'{$column}' => \$this->{$column},";
                    break;
                default:
                    $likeConditions[] = "->andFilterWhere(['like', '{$column}', \$this->{$column}])";
                    break;
            }
        }

        $conditions = [];
        if (!empty($hashConditions)) {
            $conditions[] = "\$query->andFilterWhere([\n"
                    . str_repeat(' ', 12) . implode("\n" . str_repeat(' ', 12), $hashConditions)
                    . "\n" . str_repeat(' ', 8) . "]);\n";
        }
        if (!empty($likeConditions)) {
            $conditions[] = "\$query" . implode("\n" . str_repeat(' ', 12), $likeConditions) . ";\n";
        }

        return $conditions;
    }

    /**
     * Generates URL parameters
     * @return string
     */
    public function generateUrlParams() {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pks = $class::primaryKey();
        if (count($pks) === 1) {
            return "'id' => \$model->{$pks[0]}";
        } else {
            $params = [];
            foreach ($pks as $pk) {
                $params[] = "'$pk' => \$model->$pk";
            }

            return implode(', ', $params);
        }
    }

    /**
     * Generates action parameters
     * @return string
     */
    public function generateActionParams() {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pks = $class::primaryKey();
        if (count($pks) === 1) {
            return '$id';
        } else {
            return '$' . implode(', $', $pks);
        }
    }

    /**
     * Generates parameter tags for phpdoc
     * @return array parameter tags for phpdoc
     */
    public function generateActionParamComments() {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        $pks = $class::primaryKey();
        if (($table = $this->getTableSchema()) === false) {
            $params = [];
            foreach ($pks as $pk) {
                $params[] = '@param ' . (substr(strtolower($pk), -2) == 'id' ? 'integer' : 'string') . ' $' . $pk;
            }

            return $params;
        }
        if (count($pks) === 1) {
            return ['@param ' . $table->columns[$pks[0]]->phpType . ' $id'];
        } else {
            $params = [];
            foreach ($pks as $pk) {
                $params[] = '@param ' . $table->columns[$pk]->phpType . ' $' . $pk;
            }

            return $params;
        }
    }

    /**
     * Returns table schema for current model class or false if it is not an active record
     * @return boolean|TableSchema
     */
    public function getTableSchema() {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
            return $class::getTableSchema();
        } else {
            return false;
        }
    }

    /**
     * @return array model column names
     */
    public function getColumnNames() {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
            return $class::getTableSchema()->getColumnNames();
        } else {
            /** @var Model $model */
            $model = new $class();

            return $model->attributes();
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
