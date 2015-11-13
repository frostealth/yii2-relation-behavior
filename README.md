# Yii2 Relation Behavior

Easy linking and sync relationships many-to-many.

## Installation

Run the [Composer](http://getcomposer.org/download/) command to install the latest stable version:
```
composer require frostealth/yii2-relation-behavior @stable
```

## Using the SyncRelationBehavior

### Attach the behavior to your model

```php
public function behaviors()
{
    return [
        SyncRelationBehavior::className(),
    ];
}
```

### Sync

Use the `sync` method to construct many-to-many associations. 
The `sync` method accepts an array of IDs.

```php
$model->sync('categories', [2, 5, 9]);
```

## Using the EasyRelationBehavior

The `EasyRelationBehavior` extends the `SyncRelationBehavior`.

### Attach the behavior to your model

```php
// ...

public function behaviors()
{
    return [
        [
            'class' => EasyRelationBehavior::className(),
            'relations' => ['categories'],
            'suffix' => 'ids', // by default
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

### Just add your `$suffix` to the relation name and you will get associated ids

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

## License

The MIT License (MIT).
See [LICENSE.md](https://github.com/frostealth/yii2-relation-behavior/blob/master/LICENSE.md) for more information.