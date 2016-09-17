<?php

namespace mhndev\yii2Repository\Interfaces;

/**
 * Interface iRepository
 * @package mhndev\yii2Repository\Interfaces
 */
interface iRepository
{

    const desc = 'DESC';

    const asc  = 'ASC';

    const PRIMARY_KEY = '_id';

    const APPLICATION_KEY = 'id';


    /**
     * @param array $with
     * @return $this
     */
    public function with(array $with = []);

    /**
     * @param array $columns
     * @return $this
     */
    public function columns(array $columns = ['*']);

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit = 10);

    /**
     * @param $orderBy
     * @param string $sort
     * @return $this
     */
    public function orderBy($orderBy, $sort = 'DESC');

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data);


    /**
     * @param array $data
     * @return mixed
     */
    public function createMany(array $data);


    /**
     * @param $id
     * @param bool $returnArray
     * @return mixed
     */
    public function findOneById($id, $returnArray = false);

    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @param bool $returnArray
     * @return mixed
     */
    public function findOneBy($key, $value, $operation = '=', $returnArray = false);

    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @param $
     * @param bool $withPagination
     * @param bool $returnArray
     * @return mixed
     */
    public function findManyBy($key, $value, $operation = '=', $withPagination = true, $returnArray = false);


    /**
     * @param array $ids
     * @param bool $withPagination
     * @param bool $returnArray
     * @return mixed
     */
    public function findManyByIds(array $ids, $withPagination = true, $returnArray = false);

    /**
     * @param bool $withPagination
     * @param bool $returnArray
     * @return mixed
     * @internal param $ $
     */
    public function findAll($withPagination = true, $returnArray = false);

    /**
     * @param array $criteria
     * @param bool $withPagination
     * @param array $with
     * @param $
     * @param bool $returnArray
     * @return mixed
     */
    public function findManyByCriteria(array $criteria = [], $withPagination = true, $with = [], $returnArray = false);


    /**
     * @param $id
     * @param array $data
     * @return boolean
     */
    public function updateOneById($id, array $data = []);

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @return boolean
     */
    public function updateOneBy($key, $value, array $data = []);

    /**
     * @param array $criteria
     * @param array $data
     * @return boolean
     */
    public function updateOneByCriteria(array $criteria, array $data = []);

    /**
     * @param $key
     * @param $value
     * @param array $data
     * @param string $operation
     * @return bool
     */
    public function updateManyBy($key, $value, array $data = [], $operation = '=');

    /**
     * @param array $criteria
     * @param array $data
     * @return boolean
     */
    public function updateManyByCriteria(array $criteria = [], array $data = []);


    /**
     * @param array $ids
     * @param array $data
     * @return bool
     */
    public function updateManyByIds(array $ids, array $data = []);

    /**
     * @param $id
     * @return boolean
     */
    public function deleteOneById($id);


    /**
     * @param array $ids
     * @return bool
     */
    public function allExist(array $ids);

    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @return bool
     */
    public function deleteOneBy($key, $value, $operation = '=');

    /**
     * @param array $criteria
     * @return boolean
     */
    public function deleteOneByCriteria(array $criteria = []);

    /**
     * @param $key
     * @param $value
     * @param string $operation
     * @return bool
     */
    public function deleteManyBy($key, $value, $operation = '=');

    /**
     * @param array $criteria
     * @return boolean
     */
    public function deleteManyByCriteria(array $criteria = []);

    /**
     * @return mixed
     */
    public function searchByCriteria();


    /**
     * @param array $ids
     * @return mixed
     */
    public function deleteManyByIds(array $ids);


    /**
     * @param $id
     * @param $field
     * @param int $count
     * @return
     */
    public function inc($id, $field, $count = 1);

    /**
     * @param $id
     * @param $field
     * @param int $count
     * @return
     */
    public function dec($id, $field, $count = 1);
}
