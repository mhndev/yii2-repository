<?php
/**
 * Created by PhpStorm.
 * User: majid
 * Date: 9/5/16
 * Time: 10:02 AM
 */
namespace mhndev\yii2Repository\Traits;

use mhndev\yii2Repository\Exceptions\RepositoryException;
use Yii;
use yii\data\Pagination;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\Query;

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
 * trait SqlArRepositoryTrait
 * @package mhndev\yii2Repository
 */
trait SqlArRepositoryTrait
{
    /**
     * @var Connection
     */
    protected $connection;


    /**
     * @var string
     */
    public $modelClass;

    /**
     * @var ActiveRecord
     */
    protected $model;

    /**
     * @var Query
     */
    protected $query;

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
     * @var array|string
     */
    protected $orderBy = [self::PRIMARY_KEY => self::desc];

    /**
     * @var int
     */
    protected $limit = 10;

    /**
     * @var int
     */
    protected $offset = 0;


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

        $this->columns();

        $this->orderBy(self::PRIMARY_KEY, self::desc);

        $this->connection = \Yii::$app->db;
        $this->query = $this->model->find();
    }


    /**
     * @return $this
     */
    protected function initRepositoryParams()
    {
        $this->columns();
        $this->orderBy(self::PRIMARY_KEY, self::desc);

        $this->connection = Yii::$app->db;
        $this->query = $this->model->find();

        return $this;
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

        $this->columns = $columns;

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

            $this->orderBy = [$orderBy => 'SORT_'.$sort];
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
        $this->model->setAttributes($data);
        $this->model->save();

        return $this->model;
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
            ->batchInsert($modelClassName::tableName(), $modelClassName->attributes(), $data)->execute();
    }


    /**
     * @param $id
     * @param bool $returnArray
     * @return mixed
     */
    public function findOneById($id, $returnArray = false)
    {
        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }

        $this->initFetch($returnArray, $this->columns);
        return $this->query->where([self::PRIMARY_KEY=>$id])->one();
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
        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }
        $this->initFetch($returnArray, $this->columns);

        return $this->query->where([$operation, $key ,$value])->one();
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
        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }

        $this->initFetch($returnArray, $this->columns);
        $this->query = $this->query->where([$operation, $key , $value])->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->query->all();
    }

    /**
     * @param array $ids
     * @param bool $withPagination
     * @param bool $returnArray
     * @return mixed
     */
    public function findManyByIds(array $ids, $withPagination = true, $returnArray = false)
    {
        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }

        $this->initFetch($returnArray, $this->columns);
        $this->query = $this->query->where([self::PRIMARY_KEY=>$ids])->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->query->all();
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
        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }

        $this->initFetch($returnArray, $this->columns);
        $this->query = $this->query->where([$field => $values])->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->query->all();
    }


    /**
     * @param int $perPage
     * @return array
     */
    protected function paginate($perPage = 10)
    {
        $response = [];

        $count = $this->query->count();

        $pagination = new Pagination(['totalCount' => $count,'pageSize' => $perPage ]);


        $result = $this->query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $response['items'] = $result;
        $response['_meta']['totalCount'] = $count;
        $response['_meta']['pageCount'] = floor($count / $pagination->limit )+ 1;
        $response['_meta']['currentPage'] = !empty($_GET['page']) ? $_GET['page'] : 1;
        $response['_meta']['perPage'] = $pagination->limit;

        $response['_links'] = $pagination->getLinks();

        return $response;
    }


    /**
     * @param $returnArray
     * @param $columns
     */
    protected function initFetch($returnArray, $columns)
    {
        if($columns != ['*']){
            $this->query->select($columns);
        }

        if($returnArray){
            $this->query->asArray();
        }
    }

    /**
     * @param bool $withPagination
     * @param bool $returnArray
     * @return mixed
     */
    public function findAll($withPagination = true, $returnArray = false)
    {
        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }

        $this->initFetch($returnArray, $this->columns);

        $this->query = $this->query->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->query->all();
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
        if(depth($criteria) > 1)
            array_unshift($criteria, 'and');

        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }

        $this->initFetch($returnArray, $this->columns);
        $this->query = $this->query->where($criteria)->orderBy($this->orderBy);


        return $withPagination ? $this->paginate() : $this->query->all();
    }

    /**
     * @param $id
     * @param $field
     * @param int $count
     */
    public function inc($id, $field, $count = 1)
    {
        $entity = $this->query->One([self::PRIMARY_KEY=>$id]);

        $entity->updateCounters([$field => $count]);
    }

    /**
     * @param $id
     * @param $field
     * @param int $count
     */
    public function dec($id, $field, $count = -1)
    {
        $entity = $this->query->one([self::PRIMARY_KEY=>$id]);

        $entity->updateCounters([$field => $count]);
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
     * @return mixed
     */
    public function updateOneById($id, array $data = [])
    {
        $entity = $this->query->one([self::PRIMARY_KEY=>$id]);

        return $this->updateEntity($entity, $data);

    }

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @return mixed
     */
    public function updateOneBy($key, $value, array $data = [])
    {
        $entity = $this->query->one([ $key => $value ]);

        return $this->updateEntity($entity, $data);

    }

    /**
     * @param array $criteria
     * @param array $data
     * @return mixed
     */
    public function updateOneByCriteria(array $criteria, array $data = [])
    {
        $entity = $this->query->one($criteria);

        return $this->updateEntity($entity, $data);
    }


    /**
     * @param $key
     * @param $value
     * @param array $data
     * @param string $operation
     * @return int number of records updated
     */
    public function updateManyBy($key, $value, array $data = [], $operation = '=')
    {
        return $this->model->updateAll($data, [$operation, $key, $value]);
    }

    /**
     * @param array $criteria
     * @param array $data
     * @return int number of records updated
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
     * @return int number of records updated
     */
    public function updateManyByIds(array $ids, array $data = [])
    {
        return $this->model->updateAll($data, ['in', self::PRIMARY_KEY, $ids]);
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
     * @param $id
     * @return boolean|integer number of rows deleted
     */
    public function deleteOneById($id)
    {
        $entity = $this->model->findOne([self::PRIMARY_KEY=>$id]);

        return $entity->delete();
    }


    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @return bool|int number of rows deleted
     */
    public function deleteOneBy($key, $value, $operation = '=')
    {
        $entity = $this->model->findOne([$operation, $key, $value]);

        return $entity->delete();
    }

    /**
     * @param array $criteria
     * @return boolean|integer number of rows deleted
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
     * @return bool|int number of rows deleted
     */
    public function deleteManyBy($key, $value, $operation = '=')
    {
        return  $this->model->deleteAll([$operation, $key, $value]);
    }

    /**
     * @param array $criteria
     * @return boolean|integer number of rows deleted
     */
    public function deleteManyByCriteria(array $criteria = [])
    {
        if(depth($criteria) > 1)
            array_unshift($criteria, 'and');

        return $this->model->deleteAll($criteria);
    }



    /**
     * @param array $ids
     * @return boolean|integer number of rows deleted
     */
    public function deleteManyByIds(array $ids)
    {
        return $this->model->deleteAll(['in', self::PRIMARY_KEY, $ids]);
    }


    /**
     * @return mixed
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


        if(!empty($_GET['perPage'])){
            $this->limit($_GET['perPage']);
        }

        if(!empty($_GET['page'])){
            $this->offset($_GET['page'] * $this->limit);
        }


        if(!empty($search)){
            $criteria = [];
            foreach ($search as $string){
                $components = explode(':', $string);

                array_push($criteria ,[$components[1],$components[0],$components[2]]);
            }

            return $this->findManyByCriteria($criteria);

        }else{
            return $this->findAll();
        }

    }


}
