# Yii2 Relation Behavior

Easy linking relationships one-to-many.

## Installation

Run the [Composer](http://getcomposer.org/download/) command to install the latest stable version:
```
composer require frostealth/yii2-relation-behavior @stable
```

## Usage

### Attach the behavior to your model

```php
// ...

public function behaviors()
{
    return [
        [
            'class' => RelationBehavior::className(),
            'relations' => ['categories'],
            'rawSuffix' => 'ids', // default 'raw'
        ],
    ];
}

public function rules()
{
    return [
        ['categoriesIds', 'each', 'rule' => ['integer', 'integerOnly' => true]],
    ];
}

// ...
```

### Just add your `$rawSuffix` to the relation name and you will get associated ids

```php
$categoriesIds = $model->categoriesIds; // [1, 3, 4]

// linking
$categoriesIds = [2, 3, 5];
$model->categoriesIds = $categoriesIds;
$model->save();
```

### Add control to view for managing related list

Without extensions it can be done with multiple select:

```php
<?php $categories = ArrayHelper::map(Category::find()->all(), 'id', 'name') ?>
<?= $form->field($model, 'categoriesIds')->dropDownList($categories, ['multiple' => true]) ?>
```