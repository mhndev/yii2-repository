<?php
/**
 * Created by PhpStorm.
 * User: majid
 * Date: 9/7/16
 * Time: 9:37 PM
 */
namespace mhndev\yii2Repository;

use mhndev\yii2Repository\Exceptions\RepositoryException;
use mhndev\yii2Repository\Interfaces\iRepository;
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
 * Class AbstractMongoArRepository
 * @package mhndev\yii2Repository
 */
class AbstractMongoArRepository implements iRepository
{
    /**
     * @var ActiveRecord
     */
    protected $model;


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
     * AbstractMongoRepository constructor.
     * @param ActiveRecord $model
     */
    public function __construct(ActiveRecord $model)
    {
        $this->connection = Yii::$app->mongodb;
        $this->model = $model;

        $this->columns();
        $this->orderBy(self::PRIMARY_KEY, self::desc);


        $this->query = $this->model->find();
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

        $this->model->save();

        return $this->formatEntity($this->model);
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
     * @param $entity
     * @return mixed
     */
    protected function formatEntity($entity)
    {
        if(in_array(self::APPLICATION_KEY, $this->columns)){
            $entity[self::APPLICATION_KEY] = $entity[self::PRIMARY_KEY];

            if($entity[self::PRIMARY_KEY] instanceof ObjectID){
                $entity[self::APPLICATION_KEY] = $entity[self::PRIMARY_KEY]->__toString();
            }

        }


        unset($entity[self::PRIMARY_KEY]);

        return $entity;
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
     * @param array $entities
     * @return array
     */
    protected function formatEntitiesObject(array $entities)
    {
        $result = [];


        foreach ($entities as $entity){
            if(!in_array(self::APPLICATION_KEY, $this->columns)){

                unset($entity->{self::PRIMARY_KEY});
            }
            $model = $this->formatEntityObject($entity);

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
        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }

        $condition = ($operation == '=') ? [$key => $value] : [$operation, $key ,$value];

        $this->initFetch($returnArray, $this->columns);

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
        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }

        $this->initFetch($returnArray, $this->columns);
        $this->query = $this->model->find()->where([$operation, $key , $value])->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->formatEntityObject($this->query->all() );
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
        $this->query = $this->model->find()->where([self::PRIMARY_KEY=>$ids])->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->formatEntitiesObject($this->query->all() );
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

        return $withPagination ? $this->paginate() : $this->formatEntitiesObject($this->query->all() );
    }


    /**
     * @param array $entities
     * @return array
     */
    protected function formatEntities(array $entities)
    {
        $result = [];
        foreach ($entities as $item){
            $result[] = $this->formatEntity($item);
        }

        return $result;
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

        $response['items'] = $this->formatEntitiesObject($result);

        $response['_meta']['totalCount'] = $count;
        $response['_meta']['pageCount'] = floor($count / $pagination->limit )+ 1;
        $response['_meta']['currentPage'] = !empty($_GET['page']) ? $_GET['page'] : 1;
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

        foreach ($this->with as $relation){
            $this->query = $this->query->with($relation);
        }
        $this->initFetch($returnArray, $this->columns);

        $this->query = $this->query->where($mainCriteria)->orderBy($this->orderBy);

        return $withPagination ? $this->paginate() : $this->formatEntitiesObject($this->query->all() );
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
        $entity = $this->makeQuery()->findOne([self::PRIMARY_KEY=>$id]);

        $entity->updateCounters([$field => $count]);
    }

    /**
     * @param $id
     * @param $field
     * @param $count
     */
    public function dec($id, $field, $count = 1)
    {
        $entity = $this->makeQuery()->findOne([self::PRIMARY_KEY=>$id]);

        $entity->updateCounters([$field => $count]);
    }
}
