<?php namespace Loilo\ContaoExecBundle\Util;

/**
 * Cast various structures to serializable data
 */
class Caster
{
    /**
     * Cast Laravel Collections
     *
     * @param \Illuminate\Support\Collection $collection
     * @return array
     */
    public static function castCollection($collection)
    {
        return $collection->all();
    }

    /**
     * Cast Contao models
     *
     * @param \Contao\Model $model
     * @return array
     */
    public static function castModel($model)
    {
        $class = get_class($model);
        $parentClass = get_parent_class($model);
        $classReflection = new \ReflectionClass($class);

        $arrDataProperty = $classReflection->getProperty('arrData');
        $arrDataProperty->setAccessible(true);
        $arrData = $arrDataProperty->getValue($model);

        $arrRelationsProperty = $classReflection->getProperty('arrRelations');
        $arrRelationsProperty->setAccessible(true);
        $arrRelations = $arrRelationsProperty->getValue($model);

        $baseMethods = get_class_methods($parentClass);
        $ownMethods = get_class_methods($class);
        $nonBaseMethods = array_diff($ownMethods, $baseMethods);

        return [
            'table' => $model->getTable(),
            'extends' => $parentClass,
            'fields' => $arrData,
            'relations' => $arrRelations,
            'methods' => array_values($nonBaseMethods)
        ];
    }
}
