<?php
namespace frontend\controllers;

use app\models\RespondForm;
use app\models\Review;
use app\models\TaskCompletionForm;
use app\models\TaskCreate;
use app\models\TaskRespond;
use common\models\User;
use frontend\components\DebugHelper\DebugHelper;
use Yii;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\Task;
use app\models\Category;
use yii\web\NotFoundHttpException;
use app\models\TasksFilter;
use yii\web\Response;

class TasksController extends SecuredController
{
    public function behaviors()
    {
        $rules = parent::behaviors();
        $rule = [
            'allow' => false,
            'actions' => [
                'create'
            ],
            'matchCallback' => function($rule, $action) {
                $user = Yii::$app->user->identity;
                return $user && $user->role === User::ROLE_EXECUTOR;
            },
            'denyCallback' => function($rule, $action) {
                return $action->controller->redirect(Task::getBaseTasksUrl());
            },
        ];

        array_unshift($rules['access']['rules'], $rule);
        return $rules;
    }

    public function actionIndex()
    {
        $tasks = Task::find()->where([
            'status' => Task::STATUS_NEW,
        ]);
        $taskModel = new TasksFilter();

        if(Yii::$app->request->isPost) {
            $taskModel->load(Yii::$app->request->post());
        }
        $taskModel->applyFilters($tasks);

        return $this->render('index', [
            'tasks' => $tasks->with(['category', 'author'])->orderBy('date_start DESC')->all(),
            'taskModel' => $taskModel,
            'categories' => Category::find()->all(),
            'period' => TasksFilter::PERIOD_LIST,
        ]);
    }

    public function actionView(int $id)
    {
        $task = Task::find()->with('category', 'author', 'files', 'responds')
            ->where(['id' => (int) $id])->one();
        $taskUrl = $task->getCurrentTaskUrl();
        $user = Yii::$app->user->identity;

        $respondModel = new RespondForm();
        $userRespond = TaskRespond::find()->where("task_id = $task->id AND user_id = $user->id")->one();
        $isRespond = $userRespond ? true : false;
        $taskCompletionModel = new TaskCompletionForm();

        if(!$task) {
            throw new NotFoundHttpException("Страница не найдена!");
        }

        if(Yii::$app->request->post('RespondForm') && !$isRespond) {
            if($respondModel->load(Yii::$app->request->post()) && $respondModel->validate()) {
                (new TaskRespond([
                    'user_id' => $user->id,
                    'task_id' => $task->id,
                    'text' => $respondModel->text,
                    'price' => $respondModel->text,
                    'status' => TaskRespond::STATUS_NEW,
                ]))->save();
                $this->redirect($taskUrl);
            }
        }
        if(Yii::$app->request->post('refusal-btn')) {
            $userRespond->delete();
            $this->redirect($taskUrl);
        }
        if(Yii::$app->request->post('TaskCompletionForm')) {
            if($taskCompletionModel->load(Yii::$app->request->post()) && $taskCompletionModel->validate()) {
                $task->status = $taskCompletionModel->isCompletion === TaskCompletionForm::STATUS_YES ? Task::STATUS_COMPLETED : Task::STATUS_FAILING;
                $task->save();

                (new Review([
                    'text' => $taskCompletionModel->text,
                    'rating' => $taskCompletionModel->rating,
                    'task_id' => $task->id,
                    'author_id' => $task->author_id,
                    'executor_id' => $task->executor_id,
                ]))->save();
                $this->goHome();
            }
        }

        return $this->render('view', [
            'task' => $task,
            'taskLocation' => $task->getLocation(),
            'isAuthor' => $user->id === $task->author_id,
            'isExecutor' => $user->role === User::ROLE_EXECUTOR,
            'isRespond' => $isRespond,
            'respondModel' => $respondModel,
            'taskCompletionModel' => $taskCompletionModel
        ]);
    }

    public function actionRespondAjaxValidation()
    {
        if(Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $respondModel = new RespondForm();
            $respondModel->setAttributes(Yii::$app->request->post('RespondForm'));
            return ActiveForm::validate($respondModel);
        }
    }

    // действия автора задания с откликаками на задания
    public function actionDecision(string $status, int $id, int $taskId)
    {
        $task = Task::findOne($taskId);
        $taskUrl = $task->getCurrentTaskUrl();
        $taskRespond = TaskRespond::findOne($id);

        // если действие пытается совершить не автор задания, то происходит редирект
        if(Yii::$app->user->identity->id !== $task->author_id) {
            $this->redirect($taskUrl);
        }

        // принятие отклика задания
        if($status === TaskRespond::STATUS_ACCEPTED) {
            $taskRespond->status = TaskRespond::STATUS_ACCEPTED;
            $task->status = Task::STATUS_EXECUTION;
            $task->executor_id = $taskRespond->user_id;
            $task->save();
        } else { // отклонение отклика задания
            $taskRespond->status = TaskRespond::STATUS_DENIED;
        }

        $taskRespond->save();
        $this->redirect($taskUrl);
    }

    public function actionCreate()
    {
        $model = new TaskCreate();
        if(Yii::$app->request->post() && $model->load(Yii::$app->request->post()) && $model->validate()) {
            (new Task([
                'author_id' => Yii::$app->user->getId(),
                'title' => $model->title,
                'description' => $model->description,
                'category_id' => $model->categoryId,
                'price' => $model->price,
                'latitude' => $model->latitude,
                'longitude' => $model->longitude,
                'date_end' => $model->dateEnd,
                'city_id' => $model->cityId,
                'date_start' => date("Y-m-d h:i:s"),
                'status' => Task::STATUS_NEW,
            ]))->save();
            $this->redirect(Task::getBaseTasksUrl());
        }

        return $this->render('create', [
            'model' => $model,
            'categories' => ArrayHelper::map(Category::find()->all(), 'id', 'title'),
        ]);
    }

    public function actionAjaxGetYandexPlace(string $place = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return json_decode(Yii::$container->get('yandexMap')->getDataMap($place))
            ->response->GeoObjectCollection->featureMember;
    }
}
