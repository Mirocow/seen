<?php namespace app\commands;

use \Yii;
use \yii\console\Controller;

use \app\components\MovieDb;
use \app\models\Show;
use \app\models\Episode;

/**
 * Sync data with TheMovieDB.
 */
class SyncController extends Controller
{
	public $force = false;

	public $debug = false;

	public function options($actionId)
	{
		return [
			'force',
			'debug',
		];
	}

	public function actionShows()
	{
		$movieDb = new MovieDb;

		$shows = Show::find();

		if (!$this->force)
			$shows = $shows->where(['updated_at' => null]);

		if ($this->debug) {
			$showCount = $shows->count();
			$i = 1;
		}

		foreach ($shows->each() as $show) {
			if ($this->debug) {
				echo "Show {$i}/{$showCount}\n";
				$i++;
			}

			$movieDb->syncShow($show);
		}

		return 0;
	}

	public function actionSeasons()
	{
		$movieDb = new MovieDb;

		$seasons = Season::find()
			->with('show');

		if (!$this->force)
			$seasons = $seasons->where(['updated_at' => null]);

		if ($this->debug) {
			$seasonCount = $seasons->count();
			$i = 1;
		}

		foreach ($seasons->each() as $season) {
			if ($this->debug) {
				echo "Season {$i}/{$seasonCount}\n";
				$i++;
			}

			$movieDb->syncSeason($season);
		}

		return 0;
	}

	public function actionEpisodes()
	{
		$movieDb = new MovieDb;

		$episodes = Episode::find()
			->with('season')
			->with('season.show');

		if (!$this->force)
			$episodes = $episodes->where(['updated_at' => null]);

		foreach ($episodes->each() as $episode) {
			$attributes = $movieDb->getEpisode($episode);

			if ($attributes == false)
				continue;

			$episode->attributes = (array) $attributes;
			$episode->themoviedb_id = $attributes->id;

			if (!$episode->save()) {
				Yii::warning('Could update tv show episode {$episode->id} "' . $episode->errors . '": ' . serialize($attributes), 'application\sync');
				continue;
			}
		}

		return 0;
	}
}