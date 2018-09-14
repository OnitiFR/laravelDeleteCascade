
## DeleteCascade ##

### Installation ###

```
    composer require oniti/delete-cascade
```

### Exemple ###

```
    <?php

    namespace App;

    use Illuminate\Database\Eloquent\Model;
    use Oniti\UuidForKey\DeleteCascade;

    class Article extends Model
    {
        use DeleteCascade;

        protected $cascadeDeletes = ['posts'];
    }

    ?>
```
