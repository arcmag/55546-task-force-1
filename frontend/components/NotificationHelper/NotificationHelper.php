<?php
namespace frontend\components\NotificationHelper;

use app\models\EventRibbon;
use app\models\Task;
use common\models\User;
use Yii;
use yii\helpers\Html;

class NotificationHelper
{
    const TYPE_NEW_TASK_RESPOND = 'new-task-respond';
    const TYPE_NEW_CHAT_MESSAGE = 'new-chat-message';
    const TYPE_TASK_DENIAL = 'task-denial';
    const TYPE_TASK_START = 'task-start';
    const TYPE_TASK_COMPLETE = 'task-complete';

    private static function send(User $user, Task $task, array $data): void
    {
        (new EventRibbon([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'type' => $data['type'],
            'message' => $data['messageEvent'],
        ]))->save();
        Yii::$app->mailer->compose()
            ->setTo($user->email)
            ->setFrom([Yii::$app->params['adminEmail'] => 'admin yii-taskforce'])
            ->setSubject($data['titleMail'])
            ->setHtmlBody($data['messageMail'])
            ->send();
    }

    public static function taskRespond(User $authorTask, Task $task): void
    {
        self::send($authorTask, $task, [
            'type' => self::TYPE_NEW_TASK_RESPOND,
            'messageEvent' => 'Новый отклик к заданию',
            'titleMail' => "Новый отклик к вашему заданию $task->title",
            'messageMail' => "<p>Был оставлен новый отклик к вашему заданию " . Html::a($task->title, "http://yii-taskforce/tasks/view/$task->id") .".</p>",
        ]);
    }

    public static function taskStart(User $executorTask, Task $task): void
    {
        self::send($executorTask, $task, [
            'type' => self::TYPE_TASK_START,
            'messageEvent' => 'Вы выбраны исполнителем',
            'titleMail' => "Ваш отклик к заданию $task->title был принят",
            'messageMail' => "<p>Ваш отклик к заданию " . Html::a($task->title, "http://yii-taskforce/tasks/view/$task->id") . " был принят.</p>",
        ]);
    }

    public static function taskComplete(User $executorTask, Task $task): void
    {
        self::send($executorTask, $task, [
            'type' => self::TYPE_TASK_COMPLETE,
            'messageEvent' => 'Задание завершено',
            'titleMail' => "Задание $task->title завершено",
            'messageMail' => "<p>Заказчик завершил взятое вами задание " . Html::a($task->title, "http://yii-taskforce/tasks/view/$task->id") . ".</p>",
        ]);
    }

    public static function taskDenial(User $executorTask, Task $task): void
    {
        self::send($executorTask, $task, [
            'type' => self::TYPE_TASK_DENIAL,
            'messageEvent' => 'Исполнитель отказался от задания',
            'titleMail' => "Исполнитель отказался от задания $task->title",
            'messageMail' => "<p>Исполнитель отказался от задания " . Html::a($task->title, "http://yii-taskforce/tasks/view/$task->id") . ".</p>",
        ]);
    }
}
