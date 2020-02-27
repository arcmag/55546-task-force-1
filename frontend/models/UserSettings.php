<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "user_settings".
 *
 * @property int|null $user_id
 * @property int|null $is_hidden_contacts
 * @property int|null $is_hidden_profile
 */
class UserSettings extends ActiveRecord
{
    public static function primaryKey()
    {
        return ['user_id'];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['user_id', 'integer'],
            [['is_hidden_contacts', 'is_hidden_profile'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'is_hidden_contacts' => 'Is Hidden Contacts',
            'is_hidden_profile' => 'Is Hidden Profile',
        ];
    }
}
