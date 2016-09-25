Yii2 Repository Pattern implementation
======================================
Yii2 Repository Pattern implementation in Yii2

you can read more about repository patterns here :
http://deviq.com/repository-pattern/
http://martinfowler.com/eaaCatalog/repository.html
http://shawnmc.cool/the-repository-pattern
http://stackoverflow.com/questions/16176990/proper-repository-pattern-design-in-php

## Table of Contents

- <a href="#installation">Installation</a>
    - <a href="#composer">Composer</a>
- <a href="#methods">Methods</a>
    - <a href="#prettusrepositorycontractsrepositoryinterface">RepositoryInterface</a>

- <a href="#usage">Usage</a>
	- <a href="#create-a-model">Create a Model</a>
	- <a href="#create-a-repository">Create a Repository</a>
	- <a href="#attach-your-repository-to-container">Attach your Repository to container</a>
	- <a href="#repository-sample-usage">Repository Sample Usage</a>
	
	
## Installation

### Composer
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist mhndev/yii2-repository "1.*"
```

or add

```
"mhndev/yii2-repository": "1.*"
```

to the require section of your `composer.json` file.


## Methods

### mhndev\yii2Repository\Interfaces\iRepository

```php
    public function with(array $with = []);

    public function columns(array $columns = ['*']);

    public function limit($limit = 10);

    public function orderBy($orderBy, $sort = 'DESC');

    public function create(array $data);

    public function createMany(array $data);

    public function findOneById($id, $returnArray = false);

    public function findOneBy($key, $value, $operation = '=', $returnArray = false);

    public function findManyBy($key, $value, $operation = '=', $withPagination = true, $returnArray = false);

    public function findManyByIds(array $ids, $withPagination = true, $returnArray = false);

    public function findAll($withPagination = true, $returnArray = false);

    public function findManyByCriteria(array $criteria = [], $withPagination = true, $with = [], $returnArray = false);

    public function updateOneById($id, array $data = []);

    public function updateOneBy($key, $value, array $data = []);

    public function updateOneByCriteria(array $criteria, array $data = []);

    public function updateManyBy($key, $value, array $data = [], $operation = '=');

    public function updateManyByCriteria(array $criteria = [], array $data = []);

    public function updateManyByIds(array $ids, array $data = []);

    public function deleteOneById($id);

    public function allExist(array $ids);

    public function deleteOneBy($key, $value, $operation = '=');

    public function deleteOneByCriteria(array $criteria = []);

    public function deleteManyBy($key, $value, $operation = '=');

    public function deleteManyByCriteria(array $criteria = []);

    public function searchByCriteria();

    public function deleteManyByIds(array $ids);

    public function inc($id, $field, $count = 1);

    public function dec($id, $field, $count = 1);
```

## Usage

### Create a Model

create your ActiveRecord Model :

```php
namespace app\models;


use yii\db\ActiveRecord;

/**
 * Class City
 * @package app\models
 */
class Post extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'posts';

    }

    /**
     * @return array
     */
    public function rules()
    {
        return [

            [['title'], 'required'],

        ];
    }


}

```

### Create a Repository Interface
```php
namespace app\repositories\interfaces;
use mhndev\yii2Repository\Interfaces\iRepository;

interface iPostRepository extends iRepository
{

}

```
### Create a Repository class

```php
namespace app\repositories;

use app\repositories\interfaces\iPostRepository;
use mhndev\yii2Repository\AbstractSqlArRepository;

class PostRepository extends AbstractSqlArRepository implements iPostRepository
{

}

```

or

### create a Repository as a Yii component

```php
class PostRepository extends Component implements iPostRepository
{

    const PRIMARY_KEY = 'id';

    const APPLICATION_KEY = 'id';


    use SqlArRepositoryTrait {
        init as repositoryInit;
    }


    public function init()
    {
        parent::init();

        $this->repositoryInit();
    }
}


```

this approach is useful when toy have a class which is already extending a class
and can't extend AbstractRepository class just like yii components
by using traits you can use this classes as yii component.



if your model is using sql as it's data source and your model actually is extending yii\db\ActiveRecord
your repository should extend mhndev\yii2Repository\AbstractSqlArRepository.

and if your data source is mongodb and your model actually is extending yii\mongodb\ActiveRecord
your repository should extend mhndev\yii2Repository\AbstractMongoArRepository.


and consider your application can use power of both sql and document-based databses (mongo)
### Attach your Repository to container
```php

Yii::$container->set(\app\repositories\interfaces\iPostRepository::class, [
    'class' => \app\repositories\PostRepository::class
]);


Yii::$container->set('postRepository', function($container, $params, $config){
    return new \app\repositories\PostRepository(new \app\models\Post());
});


```
 if you are coding in a module you can put above code in your module bootstrap file.
 and also you can don't use container and create repository object every where you want like this :
 
 ```php
 $postRepository = new \app\repositories\PostRepository(new \app\models\Post());

 ```
 
### Repository Sample Usage
 
 sample usage which I show by using a controller.
 
 ```php
 namespace app\controllers;
 
 use app\repositories\interfaces\iPostRepository;
 use Yii;
 use yii\web\Controller;
 use yii\web\Response;
 
 /**
  * PostController
  */
 class PostController extends Controller
 {
 
     /**
      * @var iPostRepository
      */
     protected $postRepository;
 
 
     /**
      * @var bool
      */
     public $enableCsrfValidation = false;
 
 
     /**
      * init
      */
     public function init()
     {
         parent::init();
         $this->postRepository = Yii::$container->get('postRepository');
 
         Yii::$app->response->format = Response::FORMAT_JSON;
     }
 
     /**
      * @return array
      */
     public function verbs()
     {
         return [
             'create'   => ['POST'],
             'delete'   => ['DELETE'],
             'update'   => ['PUT'],
             'index'    => ['GET'],
             'show'     => ['GET'],
             'delete-multiple' => ['DELETE']
         ];
     }
 
 
     /**
      * @return mixed
      */
     public function actionCreate()
     {
         $data = Yii::$app->request->post();
 
         $post = $this->postRepository->create($data);
 
         return $post;
     }
 
 
     /**
      * @param $id
      */
     public function actionDelete($id)
     {
         $this->postRepository->deleteOneById($id);
     }
 
     /**
      * @param $id
      * @return bool
      */
     public function actionUpdate($id)
     {
         $data = Yii::$app->request->post();
 
         $post = $this->postRepository->updateOneById($id, $data);
 
         return $post;
     }
 
 
     /**
      * @return mixed
      */
     public function actionIndex()
     {
         return $this->postRepository->searchByCriteria();
     }
 
 
     /**
      * @param $id
      * @return mixed
      */
     public function actionShow($id)
     {
         return $this->postRepository->findOneById($id);
     }
 
 
     /**
      *
      */
     public function actionDeleteMultiple()
     {
         $ids = Yii::$app->request->post()['ids'];
 
         $deletedCount = $this->postRepository->deleteManyByIds($ids);
     }
 
 
 }

 ```
 