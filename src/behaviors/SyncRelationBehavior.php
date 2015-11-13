<?php

namespace frostealth\yii2\behaviors;

use yii\base\Behavior;
use yii\helpers\ArrayHelper;

/**
 * Class SyncRelationBehavior
 *
 * @property \yii\db\BaseActiveRecord $owner
 *
 * @package frostealth\yii2\behaviors
 */
class SyncRelationBehavior extends Behavior
{
    /**
     * Sync the relationship with a list of IDs
     *
     * @param string $name
     * @param array  $ids
     * @param bool   $delete whether to delete the model that contains the foreign key
     *
     * @return array changes
     */
    public function sync($name, array $ids, $delete = true)
    {
        /** @var \yii\db\ActiveRecordInterface $relationClass */
        $relationClass = $this->owner->getRelation($name)->modelClass;

        $current = $this->getLinkedIds($name);
        $unlink = array_diff($current, $ids);
        $link = array_diff($ids, $current);

        $models = !empty($unlink) ? $relationClass::findAll($unlink) : [];
        foreach ($models as $model) {
            $this->owner->unlink($name, $model, $delete);
        }

        $models = !empty($link) ? $relationClass::findAll($link) : [];
        foreach ($models as $model) {
            $this->owner->link($name, $model);
        }

        $this->afterSync($name);

        return ['linked' => $link, 'unlinked' => $unlink];
    }

    /**
     * @param string $relation
     *
     * @return array
     */
    protected function getLinkedIds($relation)
    {
        $models = (array)$this->owner->{$relation};
        $ids = ArrayHelper::getColumn($models, 'primaryKey', false);
        $ids = array_filter($ids);

        return $ids;
    }

    /**
     * This method is called at the end of the synchronization relationship
     *
     * @param string $name the name of synchronized relationship
     */
    protected function afterSync($name)
    {
    }
}
