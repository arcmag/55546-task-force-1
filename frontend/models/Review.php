<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "review".
 *
 * @property int $id
 * @property int|null $task_id
 * @property int|null $author_id
 * @property int|null $executor_id
 * @property string|null $text
 */
class Review extends ActiveRecord
{
    public static function tableName()
    {
        return 'review';
    }

    public function rules()
    {
        return [
            [['task_id', 'author_id', 'executor_id', 'text'], 'required'],
            [['task_id', 'author_id', 'executor_id'], 'integer'],
            [['text'], 'string'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task ID',
            'author_id' => 'Author ID',
            'executor_id' => 'Executor ID',
            'text' => 'Text',
            'rating' => 'Rating',
        ];
    }
}
