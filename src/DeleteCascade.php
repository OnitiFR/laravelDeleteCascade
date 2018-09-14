<?php

namespace Oniti\DeleteCascade;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait DeleteCascade
{
  public static function bootSoftDeleteCascade()
  {
    static::deleting(function ($model) {
      $relations = $model->getCascadeDeletesRelations();
      $deleteMethod = $this->isCascadeDeletesForceDeleting() ? 'forceDelete' : 'delete';

      foreach ($relations as $relationName => $relation) {

        $children = $model->getCascadeDeletesRelationQuery($relationName);

        switch (get_class($relation)) {
          case HasOneOrMany::class:
            $children->detach();
          break;
          case BelongsToMany::class:
            // Filtre les children afin de ne pas avoir de null potentiel
            $children = $children->get()->filter(function ($child) {
                return $child instanceof Model && $child->exists;
            })->all();

            foreach ($children as $child) {
              $child->$deleteMethod();
            }
          break;
          default:
            throw new \Exception("Relation non prise en charge : ".(get_class($relation)), 500);

          break;
        }
      }
    });
  }

  /**
  * Retourne la liste des relations a supprimer
  * Vérifie si la relation existe bien dans le modèle
  * @return [type] [description]
  */
  private function getCascadeDeletesRelations(){
    $names = $this->getCascadeDeletesRelationNames();

    return array_combine($names, array_map(function ($name) {
      $relation = method_exists($this, $name) ? $this->$name() : null;
      return $relation instanceof Relation ? $relation : null;
    }, $names));
  }
  /**
  * retourne la liste des relations a supprimées définie pas l'utilisateur
  * @return [type] [description]
  */
  private function getCascadeDeletesRelationNames(){
    return property_exists($this, 'cascadeDeletes') ? $this->cascadeDeletes : [];;
  }
  /**
   * Retourne si on doit être en force Delete ou pas
   * @return boolean [description]
   */
  private function isCascadeDeletesForceDeleting(){
    return property_exists($this, 'forceDeleting') && $this->forceDeleting;
  }
  /**
   * En cas de force delete permet de récupéréer les éléments 'soft deleted' afin de les supprimer
   * @param  [type] $relation [description]
   * @return [type]           [description]
   */
  private function getCascadeDeletesRelationQuery($relation){
    $query = $this->$relation();
    if ($this->isCascadeDeletesForceDeleting()) {
      if (!is_null($query->getMacro('withTrashed'))) {
        $query = $query->withTrashed();
      }
    }
    return $query;
  }
}
