<?php
/**
 * Created by PhpStorm.
 * User: majid
 * Date: 9/5/16
 * Time: 10:02 AM
 */
namespace mhndev\yii2Repository;

use mhndev\yii2Repository\Interfaces\iRepository;
use mhndev\yii2Repository\Traits\SqlArRepositoryTrait;
use yii\db\ActiveRecord;

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
 * $postRepository->updateManyByCriteria([['like','title','salam'],['like','text','text2']], ['text'=>'salam']);
 *
 * $postRepository->deleteManyByIds([2,3]);
 *
 * $postRepository->deleteManyBy('title','title5','like');
 *
 * $posts = $postRepository->findManyWhereIn('title',['salam','salam2'], false);
 *
 * Class AbstractSqlArRepository
 * @package mhndev\yii2Repository
 */
class AbstractSqlArRepository implements iRepository
{

    use SqlArRepositoryTrait;

    /**
     * AbstractMongoRepository constructor.
     * @param ActiveRecord $model
     */
    public function __construct(ActiveRecord $model)
    {
        $this->model = $model;

        $this->initRepositoryParams();
    }

}
