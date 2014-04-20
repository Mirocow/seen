<?php namespace app\controllers;

use \Yii;
use \yii\filters\AccessControl;
use \yii\web\Controller;
use \yii\filters\VerbFilter;

use \app\models\Show;
use \app\models\Language;
use \app\models\UserShow;
use \app\components\MovieDb;

class TvController extends Controller
{
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::className(),
				'only' => ['subscribe', 'unsubscribe'],
				'rules' => [
					[
						'actions' => ['subscribe', 'unsubscribe'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'logout' => ['post'],
				],
			],
		];
	}

	public function actionIndex()
	{
		if (Yii::$app->user->isGuest) {
			return $this->render('index');
		} else {
			$shows = Yii::$app->user->identity->getShows()
				->all();

			// Load model because cannot be loaded in `usort`
			foreach ($shows as $show) {
				$show->lastEpisode;
			}

			usort($shows, function($a, $b) {
				if ($a->lastEpisode !== null && $b->lastEpisode !== null) {
					$aTime = strtotime($a->lastEpisode->created_at);
					$bTime = strtotime($b->lastEpisode->created_at);

					if ($aTime == $bTime)
						return 0;

					return ($aTime < $bTime) ? 1 : -1;
				} elseif ($a->lastEpisode === null) {
					return 1;
				} elseif ($b->lastEpisode === null) {
					return -1;
				}

				return 0;
			});

			return $this->render('dashboard', [
				'shows' => $shows,
			]);
		}
	}

	public function actionView($slug)
	{
		$show = Show::find()
				->where(['slug' => $slug])
				->with('seasons')
				->with('creators')
				->with('cast')
				->with('crew')
				->with('language')
				->with('seasons.episodes')
				->one();
		if ($show === null)
			throw new \yii\web\NotFoundHttpException(Yii::t('Show', 'The TV Show could not be found!'));

		return $this->render('view', [
			'show' => $show,
		]);
	}

	public function actionLoad()
	{
		if (!Yii::$app->request->isPost || Yii::$app->request->post('id') === null)
			throw new yii\web\BadRequestHttpException;

		$language = Language::find()
			->where(['iso' => Yii::$app->language])
			->one();

		if ($language === null)
			$language = Language::find()
				->where(['iso' => Yii::$app->params['lang']['default']])
				->one();

		$show = Show::find()
			->where(['themoviedb_id' => Yii::$app->request->post('id')])
			->andWhere(['language_id' => $language->id])
			->one();

		if ($show !== null)
			return json_encode([
				'success' => true,
				'url' => Yii::$app->urlManager->createAbsoluteUrl(['/tv/view', 'slug' => $show->slug])
			]);

		$show = new Show;
		$show->themoviedb_id = Yii::$app->request->post('id');
		$show->language_id = $language->id;
		$show->save();

		$movieDb = new MovieDb;

		$show->slug = ''; // Rewrite slug with title
		if ($movieDb->syncShow($show)) {
			$successCount = 0;
			$errorCount = 0;
			foreach ($show->seasons as $season) {
				if ($movieDb->syncSeason($season))
					$successCount++;
				else
					$errorCount++;
			}

			if ($successCount === 0)
				return json_encode([
					'success' => false,
					'message' => Yii::t('Show', 'Error while getting season information!'),
				]);

			return json_encode([
				'success' => true,
				'url' => Yii::$app->urlManager->createAbsoluteUrl(['/tv/view', 'slug' => $show->slug])
			]);
		} else {
			return json_encode([
				'success' => false,
				'message' => Yii::t('Show', 'The TV Show could not be loaded at the moment! Please try again later.'),
			]);
		}
	}

	public function actionSubscribe($slug)
	{
		$show = Show::find()
			->where(['slug' => $slug])
			->one();
		if ($show === null)
			throw new \yii\web\NotFoundHttpException(Yii::t('Show', 'The TV Show could not be found!'));

		if ($show->isUserSubscribed) {
			Yii::$app->session->setFlash('info', Yii::t('Show', 'You are already subscribed to {name}.', ['name' => $show->name]));

			return $this->redirect(['view', 'slug' => $show->slug]);
		}

		$userShow = new UserShow;
		$userShow->show_id = $show->id;
		$userShow->user_id = Yii::$app->user->id;
		$userShow->save();

		return $this->redirect(['view', 'slug' => $show->slug]);
	}

	public function actionUnsubscribe($slug)
	{
		$show = Show::find()
			->where(['slug' => $slug])
			->one();
		if ($show === null)
			throw new \yii\web\NotFoundHttpException(Yii::t('Show', 'The TV Show could not be found!'));

		if (!$show->isUserSubscribed) {
			Yii::$app->session->setFlash('info', Yii::t('Show', 'You are not subscribed to {name}.', ['name' => $show->name]));

			return $this->redirect(['view', 'slug' => $show->slug]);
		}

		$userShow = UserShow::find()
			->where(['user_id' => Yii::$app->user->id])
			->andWhere(['show_id' => $show->id])
			->one();
		$userShow->delete();

		return $this->redirect(['view', 'slug' => $show->slug]);
	}
}
