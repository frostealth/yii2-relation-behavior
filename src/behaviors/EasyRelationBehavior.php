<?php

namespace frostealth\yii2\behaviors;

use frostealth\storage\Data;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\BaseActiveRecord;

/**
 * Class EasyRelationBehavior
 *
 * @package frostealth\yii2\behaviors
 */
class EasyRelationBehavior extends SyncRelationBehavior
{
    /**
     * @var array
     */
    public $relations = [];

    /**
     * @var string
     */
    public $suffix = 'ids';

    /**
     * @var Data
     */
    protected $values;

    /**
     * @var array
     */
    private $_marked = [];

    /**
     * @inheritDoc
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT => 'save',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'save',
        ];
    }

    /**
     * Sync the changed relationships
     */
    public function save()
    {
        foreach ($this->getChanged() as $relation) {
            $this->sync($relation, $this->values->get($relation), true);
        }
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws UnknownPropertyException
     */
    public function getRelationIds($name)
    {
        if (!$this->isRelation($name, false)) {
            throw new UnknownPropertyException('Getting unknown relation: ' . get_called_class() . '::' . $name);
        }

        if (!$this->values->has($name)) {
            $this->values->set($name, $this->getLinkedIds($name));
        }

        return $this->values->get($name);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws UnknownPropertyException
     */
    public function setRelationIds($name, $value)
    {
        if (!$this->isRelation($name, false)) {
            throw new UnknownPropertyException('Setting unknown relation: ' . get_called_class() . '::' . $name);
        }

        $this->markChanged($name);
        $this->values->set($name, $this->normalizeValue($value));
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (empty($this->relations) || !is_array($this->relations)) {
            throw new InvalidConfigException(get_called_class() . '::$relations must be set and be an array.');
        }

        $this->values = new Data();

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function __get($name)
    {
        if ($this->isRelation($name)) {
            return $this->getRelationIds($this->normalizeName($name));
        }

        return parent::__get($name);
    }

    /**
     * @inheritDoc
     */
    public function __set($name, $value)
    {
        if ($this->isRelation($name)) {
            $this->setRelationIds($this->normalizeName($name), $value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if ($this->isRelation($name)) {
            return true;
        }

        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritDoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if ($this->isRelation($name)) {
            return true;
        }

        return parent::canSetProperty($name, $checkVars);
    }

    /**
     * Remove values if the relationship has been manually synchronized
     *
     * @inheritDoc
     */
    protected function afterSync($name)
    {
        $this->values->remove($name);
        $this->dropChanged($name);
    }

    /**
     * @param string $name
     */
    protected function markChanged($name)
    {
        $this->_marked[$name] = $name;
    }

    /**
     * @param string $name
     */
    protected function dropChanged($name)
    {
        unset($this->_marked[$name]);
    }

    /**
     * @return array
     */
    protected function getChanged()
    {
        $filter = function ($name) {
            return $this->values->get($name) != $this->getLinkedIds($name);
        };

        return array_filter($this->_marked, $filter);
    }

    /**
     * @param string $name
     * @param bool   $checkSuffix
     *
     * @return bool
     */
    protected function isRelation($name, $checkSuffix = true)
    {
        $check = true;
        if ($checkSuffix && $check = $this->hasSuffix($name)) {
            $name = $this->normalizeName($name);
        }

        return $check && in_array($name, $this->relations) && $this->owner->getRelation($name, false) !== null;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function normalizeName($name)
    {
        if ($this->hasSuffix($name)) {
            $lenSuffix = strlen($this->suffix);
            $name = substr($name, 0, -$lenSuffix);
        }

        return $name;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function hasSuffix($name)
    {
        $lenSuffix = strlen($this->suffix);

        $hasSuffix = strlen($name) > $lenSuffix;
        $hasSuffix = $hasSuffix && substr_compare($name, $this->suffix, -$lenSuffix, $lenSuffix, true) == 0;

        return $hasSuffix;
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
