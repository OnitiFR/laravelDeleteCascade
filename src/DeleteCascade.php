<?php

namespace Oniti\DeleteCascade;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

trait DeleteCascade
{
  public static function bootDeleteCascade(){
    static::deleting(function ($model) {
      if($model->implementsSoftDeletes()){

        if ($invalidCascadingRelationships = $model->hasInvalidCascadingRelationships()) {
          throw new LogicException(sprintf(
            '%s [%s] must exist and return an object of type '. Relation::class,
            str_plural('Relationship', count($invalidCascadingRelationships)),
            join(', ', $invalidCascadingRelationships)
          ));
        }

        $delete = $model->forceDeleting ? 'forceDelete' : 'delete';
        foreach ($model->getActiveCascadingDeletes() as $relationship) {
          if ($model->{$relationship} instanceof Model) {
            $model->{$relationship}->{$delete}();
          } else {
            foreach ($model->{$relationship} as $child) {
              $child->{$delete}();
            }
          }
        }

      }
    });
  }

  /**
  * Si le modèle implémente soft Delete
  * @return [type] [description]
  */
  protected function implementsSoftDeletes(){
    return method_exists($this, 'runSoftDelete');
  }
  /**
  * Retounr si y'a des relations erronées
  * @return boolean [description]
  */
  protected function hasInvalidCascadingRelationships(){
    return array_filter($this->getCascadingDeletes(), function ($relationship) {
      return ! method_exists($this, $relationship) || ! $this->{$relationship}() instanceof Relation;
    });
  }
  /**
  * retourne le tableau des relations getCascadingDeletes
  * @return array
  */
  protected function getCascadingDeletes(){
    return isset($this->cascadeDeletes) ? (array) $this->cascadeDeletes : [];
  }
  /**
  * Retourne les relation soft delete
  * @return array
  */
  protected function getActiveCascadingDeletes(){
    return array_filter($this->getCascadingDeletes(), function ($relationship) {
      return ! is_null($this->{$relationship});
    });
  }
}
