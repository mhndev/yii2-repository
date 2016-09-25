<?php
/**
 * Created by PhpStorm.
 * User: majid
 * Date: 9/7/16
 * Time: 9:37 PM
 */
namespace mhndev\yii2Repository\Traits;

use mhndev\yii2Repository\Exceptions\RepositoryException;
use MongoDB\BSON\ObjectID;
use Yii;
use yii\data\Pagination;
use yii\mongodb\ActiveRecord;
use yii\mongodb\Connection;
use yii\mongodb\Query;

/**
 * Sample usage
 *
 * $posts = $postRepository->findManyBy('title','title5','like');
 *
 * $posts = $postRepository->findManyBy('title','title5');
 *
 * $posts = $postRepository->findManyByIds([1,2,3]);
 *
 * $posts = $postRepository->findManyWhereIn('text',['text1','text2']);
 *
 * $posts = $postRepository->findManyByCriteria([
 *           ['like', 'title','title'] , ['=','text','text1']
 * ]);
 *
 * $posts = $postRepository->findOneById(2);
 *
 * $postRepository->updateOneById(2, ['title'=>'new new']);
 *
 * $postRepository->updateManyByIds([1,2,3], ['title'=>'new new new']);
 *
 * $postRepository->updateManyBy('title','salam', ['text'=>'sssssssssss'], 'like');
 *
 *
 * $postRepository->updateManyByCriteria([['like','title','salam'],['like','text','text2']], ['text'=>'salam']);
 *
 * $postRepository->deleteManyByIds([2,3]);
 *
 * $postRepository->deleteManyBy('title','title5','like');
 *
 * $posts = $postRepository->findManyWhereIn('title',['salam','salam2'], false);
 *
 *
 *
 * trait MongoArRepositoryTrait
 * @package mhndev\yii2Repository
 */
trait MongoArRepositoryTrait
{
    /**
     * @var ActiveRecord
     */
    protected $model;


    /**
     * @var string
     */
    public $modelClass;

    /**
     * @var array $data
     * query parameters (sort, filters, pagination)
     */
    protected $data;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @var array
     */
    protected $columns = ['*'];

    /**
     * @var string
     */
    protected $orderBy;

    /**
     * @var int
     */
    protected $limit = 10;

    /**
     * @var int
     */
    protected $offset = 0;


    /**
     * @var Query
     */
    protected $query;


    /**
     * @var Connection
     */
    protected $connection;



    /**
     * @throws RepositoryException
     */
    public function init()
    {
        if($this->model){
            return;
        }

        if(empty($this->modelClass)){
            throw new RepositoryException('what the f ...');
        }

        $this->model = new $this->modelClass;

        $this->initRepositoryParams();
    }


    /**
     * @return $this
     */
    protected function initRepositoryParams()
    {
        $this->columns();
        $this->orderBy(self::PRIMARY_KEY, self::desc);

        $this->connection = Yii::$app->mongodb;
        $this->query = $this->model->find();

        return $this;
    }

    /**
     * @param $returnArray
     * @param $columns
     * @param $with
     */
    protected function initFetch($returnArray, $columns, $with)
    {
        foreach ($with as $relation){
            $this->query = $this->query->with($relation);
        }


        if($columns != ['*']){
            $this->query->select($columns);
        }

        if($returnArray){
            $this->query->asArray();
        }
    }



    /**
     * @return ActiveRecord
     */
    protected function makeQuery()
    {
        return $this->model;
    }


    /**
     * @param array $with
     * @return $this
     * @throws RepositoryException
     */
    public function with(array $with = [])
    {
        if (is_array($with) === false) {
            throw new RepositoryException;
        }

        $this->with = $with;

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     * @throws RepositoryException
     */
    public function columns(array $columns = ['*'])
    {
        if (is_array($columns) === false) {
            throw new RepositoryException;
        }

        else{
            $this->columns = $columns;
        }

        return $this;
    }


    /**
     * @param int $offset
     * @return $this
     * @throws RepositoryException
     */
    public function offset($offset = 0)
    {
        if (!is_numeric($offset) || $offset < 0) {
            throw new RepositoryException;
        }

        $this->offset = $offset;

        return $this;
    }


    /**
     * @param int $limit
     * @return $this
     * @throws RepositoryException
     */
    public function limit($limit = 10)
    {
        if (!is_numeric($limit) || $limit < 1) {
            throw new RepositoryException;
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * @param $orderBy
     * @param string $sort
     * @return $this
     * @throws RepositoryException
     */
    public function orderBy($orderBy, $sort = 'DESC')
    {
        if(! is_array($orderBy)){
            if ($orderBy === null)
                return $this;


            if (!in_array(strtoupper($sort), ['DESC', 'ASC'])) {
                throw new RepositoryException;
            }

            $this->orderBy = [$orderBy .' '. $sort];
        }

        else{

            foreach ($orderBy as $field => $method){
                if (!in_array(strtoupper($method), ['DESC', 'ASC'])) {
                    throw new RepositoryException;
                }
            }

            $this->orderBy = $orderBy;
        }


        return $this;
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        foreach($data as $key => $value){
            $this->model->{$key} = $value;
        }

        if($this->model->save()){
            return $this->formatEntity($this->model);
        }else{
            return $this->model->errors;
        }
    }


    /**
     * @param array $data
     * @return mixed|void
     * @throws RepositoryException
     */
    public function createMany(array $data)
    {
        if(depth($data) < 2){
            throw new RepositoryException;
        }

        /** @var ActiveRecord $modelClassName */
        $modelClassName = get_class($this->model);

        foreach ($data as $record){
            /** @var ActiveRecord $model */
            $model = new $modelClassName;

            foreach($record as $key => $value){
                $model->{$key} = $value;
            }

            if(!$model->validate()){
                break;
            }

        }

        $this->connection->createCommand()
            ->batchInsert($modelClassName::collectionName(), $modelClassName->attributes(), $data)->execute();
    }




    /**
     * @param $id
     * @param bool $returnArray
     * @return mixed
     */
    public function findOneById($id, $returnArray = false)
    {
        $this->initFetch($returnArray, $this->columns, $this->with);

        $entity = $this->query->where([self::PRIMARY_KEY=>$id])->one();

        return $this->formatEntityObject($entity);
    }


    /**
     * @param $entity
     * @return mixed
     */
    protected function formatEntityObject($entity)
    {
        $primaryKey = self::PRIMARY_KEY;
        $appKey     = self::APPLICATION_KEY;

        if(!empty($entity->{$primaryKey}) ){

            if($entity->{$primaryKey} instanceof ObjectID){
                $entity->{$primaryKey} = $entity->{$primaryKey}->__toString();
            }

            $entity->{$appKey} = $entity->{$primaryKey};
            unset($entity->{$primaryKey});
        }

        return $entity;
    }


    /**
     * @param $entity
     * @return mixed
     */
    protected function formatEntityArray($entity)
    {
        if(in_array(self::APPLICATION_KEY, $this->model->attributes())){


            $entity[self::APPLICATION_KEY] = $entity[self::PRIMARY_KEY];

            if($entity[self::PRIMARY_KEY] instanceof ObjectID){
                $entity[self::APPLICATION_KEY] = $entity[self::PRIMARY_KEY]->__toString();
            }

        }

        unset($entity[self::PRIMARY_KEY]);

        return $entity;
    }


    /**
     * @param $entity
     * @return mixed
     */
    public function formatEntity($entity)
    {
        if(is_array($entity)){
            $model = $this->formatEntityArray($entity);
        }else{
            $model = $this->formatEntityObject($entity);
        }

        return $model;
    }

    /**
     * @param array $entities
     * @return array
     */
    protected function formatEntities(array $entities)
    {
        $result = [];

        foreach ($entities as $entity){
            if(!in_array(self::APPLICATION_KEY, $this->model->attributes())){

                unset($entity->{self::PRIMARY_KEY});
            }
            $model = $this->formatEntity($entity);

            $result[] = $model;
        }

        return $result;
    }


    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @param bool $returnArray
     * @return mixed
     */
    public function findOneBy($key, $value, $operation = '=', $returnArray = false)
    {
        $this->initFetch($returnArray, $this->columns, $this->with);
        $condition = ($operation == '=') ? [$key => $value] : [$operation, $key ,$value];

        return $this->query->where($condition)->one();
    }


    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @param bool $withPagination
     * @param bool $returnArray
     * @return mixed
     */
    public function findManyBy($key, $value, $operation = '=', $withPagination = true, $returnArray = false)
    {
        $this->initFetch($returnArray, $this->columns, $this->with);

        $this->query = $this->query->where([$operation, $key , $value])->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->formatEntity($this->query->all() );
    }

    /**
     * @param array $ids
     * @param bool $withPagination
     * @param bool $returnArray
     * @return mixed
     */
    public function findManyByIds(array $ids, $withPagination = true, $returnArray = false)
    {
        $this->initFetch($returnArray, $this->columns, $this->with);

        $this->query = $this->query->where([self::PRIMARY_KEY=>$ids])->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->formatEntities($this->query->all() );
    }


    /**
     * @param $field
     * @param array $values
     * @param bool $withPagination
     * @param bool $returnArray
     * @return array
     */
    public function findManyWhereIn($field, array $values, $withPagination = true, $returnArray = false)
    {
        $this->initFetch($returnArray, $this->columns, $this->with);

        $this->query = $this->query->where([$field => $values])->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->formatEntities($this->query->all() );
    }



    /**
     * @param bool $withPagination
     * @param bool $returnArray
     * @return mixed
     */
    public function findAll($withPagination = true, $returnArray = false)
    {
        $this->initFetch($returnArray, $this->columns, $this->with);

        $this->query = $this->query->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->formatEntities($this->query->all() );
    }


    /**
     * @return array
     */
    protected function paginate()
    {
        $response = [];

        $count = $this->query->count();

        $perPage = !empty($_GET['perPage']) ? $_GET['perPage'] : $this->limit;
        $currentPage = !empty($_GET['page']) ? $_GET['page'] : 1;
        $pagination = new Pagination(['totalCount' => $count,'pageSize' => $perPage ]);

        $result = $this->query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $response['items'] = $this->formatEntities($result);
        $response['_meta']['totalCount'] = $count;
        $response['_meta']['pageCount'] = $pagination->pageCount;
        $response['_meta']['currentPage'] = $currentPage;
        $response['_meta']['perPage'] = $pagination->limit;

        $response['_links'] = $pagination->getLinks();

        return $response;
    }


    /**
     * @param array $criteria
     * @param bool $withPagination
     * @param array $with
     * @param bool $returnArray
     * @return mixed
     */
    public function findManyByCriteria(array $criteria = [], $withPagination = true, $with = [], $returnArray = false)
    {
        $mainCriteria = [];

        if(depth($criteria) > 1){

            if(count($criteria) > 1 ){
                array_unshift($mainCriteria, 'and');
            }

            foreach ($criteria as $condition){
                if($condition[0] == '='){
                    array_push($mainCriteria, [$condition[1] => $condition[2]]);
                }
                else{
                    array_push($mainCriteria, $condition);
                }
            }

        }else{
            if($criteria[0] == '='){
                array_push($mainCriteria, [$criteria[1] => $criteria[2]]);
            }else{
                $mainCriteria = $criteria;
            }
        }


        if(count($mainCriteria) == 1 && depth($mainCriteria) > 1){
            $mainCriteria = $mainCriteria[0];
        }

        $this->initFetch($returnArray, $this->columns, $this->with);


        $this->query = $this->query->where($mainCriteria)->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->formatEntities($this->query->all() );
    }


    /**
     * @param ActiveRecord $entity
     * @param array $data
     * @return ActiveRecord
     */
    protected function updateEntity(ActiveRecord $entity, array $data)
    {
        $entity->setAttributes($data);
        $entity->save();

        return $entity;
    }



    /**
     * @param $id
     * @param array $data
     * @return boolean
     */
    public function updateOneById($id, array $data = [])
    {
        $entity = $this->makeQuery()->findOne([self::PRIMARY_KEY=>$id]);

        return $this->updateEntity($entity, $data);
    }

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @return boolean
     */
    public function updateOneBy($key, $value, array $data = [])
    {
        if($key == self::APPLICATION_KEY){
            $key = self::PRIMARY_KEY;
        }

        $entity = $this->makeQuery()->findOne([ $key => $value ]);

        return $this->updateEntity($entity, $data);
    }

    /**
     * @param array $criteria
     * @param array $data
     * @return boolean
     */
    public function updateOneByCriteria(array $criteria, array $data = [])
    {
        $entity = $this->model->findOne($criteria);

        return $this->updateEntity($entity, $data);
    }

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @param string $operation
     * @return bool
     */
    public function updateManyBy($key, $value, array $data = [], $operation = '=')
    {
        if($key == self::APPLICATION_KEY){
            $key = self::PRIMARY_KEY;
        }

        return $this->model->updateAll($data, [$operation, $key, $value]);
    }

    /**
     * @param array $criteria
     * @param array $data
     * @return boolean
     */
    public function updateManyByCriteria(array $criteria = [], array $data = [])
    {
        if(depth($criteria) > 1)
            array_unshift($criteria, 'and');

        return $this->model->updateAll($data, $criteria);
    }

    /**
     * @param array $ids
     * @param array $data
     * @return bool
     */
    public function updateManyByIds(array $ids, array $data = [])
    {
        return $this->model->updateAll($data, ['in', self::PRIMARY_KEY, $ids]);
    }

    /**
     * @param $id
     * @return boolean
     */
    public function deleteOneById($id)
    {
        $entity = $this->model->findOne([self::PRIMARY_KEY=>$id]);

        return $entity->delete();
    }

    /**
     * @param array $ids
     * @return bool
     */
    public function allExist(array $ids)
    {
        // TODO: Implement allExist() method.
    }

    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @return bool
     */
    public function deleteOneBy($key, $value, $operation = '=')
    {
        if($key == self::APPLICATION_KEY){
            $key = self::PRIMARY_KEY;
        }

        $condition = ($operation == '=') ? [$key => $value] : [$operation, $key ,$value];

        $entity = $this->model->findOne([$condition]);

        return $entity->delete();
    }

    /**
     * @param array $criteria
     * @return boolean
     */
    public function deleteOneByCriteria(array $criteria = [])
    {
        $entity = $this->model->findOne($criteria);

        return $entity->delete();
    }

    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @return bool
     */
    public function deleteManyBy($key, $value, $operation = '=')
    {
        if($key == self::APPLICATION_KEY){
            $key = self::PRIMARY_KEY;
        }

        return  $this->model->deleteAll([$operation, $key, $value]);
    }

    /**
     * @param array $criteria
     * @return boolean
     */
    public function deleteManyByCriteria(array $criteria = [])
    {
        if(depth($criteria) > 1)
            array_unshift($criteria, 'and');

        return $this->model->deleteAll($criteria);
    }

    /**
     * @return mixed
     * @throws RepositoryException
     */
    public function searchByCriteria()
    {
        $search = !empty($_GET['search'])  ? explode(',',$_GET['search'])  : null;

        if(!empty($_GET['fields'])){
            $fields = explode(',',$_GET['fields']);
            $this->columns($fields);
        }

        if(!empty($perPage)){
            $this->limit($perPage);
        }

        if(!empty($_GET['with'])){
            $with = explode(',',$_GET['with']);
            $this->with($with);
        }

        if(!empty($search)){
            $criteria = [];
            foreach ($search as $string){
                $components = explode(':', $string);

                if( empty($components[0]) || empty($components[1]) || empty($components[2]) ){
                    throw new RepositoryException('search parameters are not specified correctly.');
                }

                if($components[0] == self::APPLICATION_KEY){
                    $components[0] = self::PRIMARY_KEY;
                }

                array_push($criteria ,[$components[1],$components[0],$components[2]]);
            }

            return $this->findManyByCriteria($criteria);

        }else{
            return $this->findAll();
        }

    }

    /**
     * @param array $ids
     * @return mixed
     */
    public function deleteManyByIds(array $ids)
    {
        return $this->model->deleteAll(['in', self::PRIMARY_KEY, $ids]);
    }

    /**
     * @param $id
     * @param $field
     * @param int $count
     */
    public function inc($id, $field, $count = 1)
    {
        /** @var \yii\db\ActiveRecord $entity */
        $entity = $this->query->one([self::PRIMARY_KEY=>$id]);

        $entity->updateCounters([$field => $count]);
    }

    /**
     * @param $id
     * @param $field
     * @param $count
     */
    public function dec($id, $field, $count = 1)
    {
        /** @var ActiveRecord $entity */
        $entity = $this->query->one([self::PRIMARY_KEY=>$id]);

        $entity->updateCounters([$field => $count]);
    }
}
