<?php

namespace frostealth\yii2\behaviors;

use yii\base\Behavior;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;

/**
 * Class RelationBehavior
 *
 * @property ActiveRecord $owner
 *
 * @package common\behaviors
 */
class RelationBehavior extends Behavior
{
    /**
     * @var array
     */
    public $relations = [];

    /**
     * @var string
     */
    public $rawSuffix = 'raw';

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var array
     */
    protected $oldValues = [];

    /**
     * @inheritDoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'save',
            ActiveRecord::EVENT_AFTER_UPDATE => 'save',
        ];
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws UnknownPropertyException
     */
    public function getRawRelation($name)
    {
        if (!$this->isRelation($name)) {
            throw new UnknownPropertyException('Getting unknown relation: ' . $name);
        }

        if (!isset($this->values[$name])) {
            $this->values[$name] = $this->getIds($name);
        }

        return $this->values[$name];
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws UnknownPropertyException
     */
    public function setRawRelation($name, $value)
    {
        if (!$this->isRelation($name)) {
            throw new UnknownPropertyException('Setting unknown relation: ' . $name);
        }

        if (!isset($this->values[$name])) {
            $this->values[$name] = $this->getIds($name);
        }

        $this->oldValues[$name] = $this->values[$name];
        $this->values[$name] = $this->normalizeValue($value);
    }

    public function save()
    {
        foreach ($this->getChanged() as $relation) {
            /** @var \yii\db\ActiveRecord $relationClass */
            $relationClass = $this->owner->getRelation($relation)->modelClass;

            $newIds = $this->values[$relation];
            $oldIds = $this->oldValues[$relation];

            $models = $relationClass::findAll(array_diff($oldIds, $newIds));
            foreach ($models as $model) {
                $this->owner->unlink($relation, $model, true);
            }

            $models = $relationClass::findAll(array_diff($newIds, $oldIds));
            foreach ($models as $model) {
                $this->owner->link($relation, $model);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function __get($name)
    {
        if ($this->isRawRelation($name)) {
            return $this->getRawRelation($this->normalizeRelationName($name));
        }

        return parent::__get($name);
    }

    /**
     * @inheritDoc
     */
    public function __set($name, $value)
    {
        if ($this->isRawRelation($name)) {
            $this->setRawRelation($this->normalizeRelationName($name), $value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if ($this->isRawRelation($name)) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritDoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if ($this->isRawRelation($name)) {
            return true;
        }

        return parent::canSetProperty($name, $checkVars);
    }

    /**
     * @param string $relation
     *
     * @return array
     */
    protected function getIds($relation)
    {
        $models = (array)$this->owner->{$relation};
        $ids = [];
        foreach ($models as $model) {
            if ($model instanceof ActiveRecord) {
                $ids[] = $model->primaryKey;
            }
        }

        return $ids;
    }

    /**
     * @return array
     */
    protected function getChanged()
    {
        return array_keys($this->oldValues);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function isRelation($name)
    {
        return in_array($name, $this->relations) && $this->owner->getRelation($name, false);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function isRawRelation($name)
    {
        $lenSuffix = strlen($this->rawSuffix);

        $hasSuffix = strlen($name) > $lenSuffix;
        $hasSuffix = $hasSuffix && substr_compare($name, $this->rawSuffix, -$lenSuffix, $lenSuffix) == 0;

        return $hasSuffix && $this->isRelation($this->normalizeRelationName($name));
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function normalizeRelationName($name)
    {
        $lenSuffix = strlen($this->rawSuffix);

        return substr($name, 0, -$lenSuffix);
    }

    /**
     * @param mixed $value
     *
     * @return array
     */
    protected function normalizeValue($value)
    {
        return !empty($value) ? (array)$value : [];
    }
}
