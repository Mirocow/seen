<?php namespace app\components;

use \Yii;

use \Tmdb\ApiToken;
use \Tmdb\Client;

use \app\models\Show;
use \app\models\Person;
use \app\models\ShowRuntime;
use \app\models\Genre;
use \app\models\Network;
use \app\models\Country;
use \app\models\Season;
use \app\models\ShowCreator;
use \app\models\ShowGenre;
use \app\models\ShowNetwork;
use \app\models\ShowCountry;
use \app\models\Episode;
use \app\models\ShowCast;
use \app\models\ShowCrew;

class MovieDb
{
	private $key = '';
	private $cache = 0;

	protected $errors = [];

	public function __construct()
	{
		$this->key = Yii::$app->params['themoviedb']['key'];
	}

	protected function get($path, $parameters)
	{
		$this->cache++;

		if ($this->cache == 30) {
			sleep(10);
			$this->cache = 1;
		}

		$parameters = array_merge_recursive($parameters, [
			'api_key' => $this->key,
		]);
		$url = Yii::$app->params['themoviedb']['url'] . $path . '?' . http_build_query($parameters);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);

		$response = curl_exec($curl);
		if ($response === false) {
			Yii::error("Error while requesting {$path}");
			$this->errors[] = "Error while requesting {$path}";
			return false;
		}

		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($status >= 400) {
			Yii::error("Error while requesting {$url}, code #{$status}: " . $response);
			$this->errors[] = "Error while requesting {$path}, code #{$status}: " . $response;
			return false;
		}

		$result = json_decode($response);
		curl_close($curl);

		return $result;
	}

	public function lastError()
	{
		return end($this->errors);
	}

	public function findTvDb($id, $language, $name, $imdbid = '', $year = '')
	{
		// Search by tvdb id
		$results = $this->get(sprintf('/find/%s', $id), [
			'external_source' => 'tvdb_id',
			'language' => $language,
		]);

		if (isset($results->tv_results) && count($results->tv_results)) {
			echo "Found by TheTVDb";
			return (array) $results->tv_results[0];
		}

		// Search by imdb id
		if (!empty($imdbid)) {
			$results = $this->get(sprintf('/find/%s', $imdbid), [
				'external_source' => 'imdb_id',
				'language' => $language,
			]);

			if (isset($results->tv_results) && count($results->tv_results)) {
				echo "Found by IMDB";
				return (array) $results->tv_results[0];
			}
		}

		// Search by name and year
		$parameters = [
			'query' => $name,
			'language' => $language,
		];
		if (!empty($year)) {
			$parameters['year'] = $year;
		}
		$results = $this->get('/search/tv', $parameters);
		if (isset($results->results) && count($results->results)) {
			echo "Found by search and year";

			return (array) $results->results;
		}

		// Search by name
		$parameters = [
			'query' => $name,
			'language' => $language,
		];
		$results = $this->get('/search/tv', $parameters);
		if (isset($results->results) && count($results->results)) {
			echo "Found by search";

			return (array) $results->results;
		}

		return false;
	}

	public function getShow($show)
	{
		return $this->get(sprintf('/tv/%s', $show->themoviedb_id), [
			'language' => $show->language->iso,
			'append_to_response' => 'credits',
		]);
	}

	public function getSeason($season)
	{
		return $this->get(sprintf('/tv/%s/season/%s', $season->show->themoviedb_id, $season->number), [
			'language' => $season->show->language->iso,
		]);
	}

	public function getEpisode($episode)
	{
		return $this->get(sprintf('/tv/%s/season/%s/episode/%s', $episode->season->show->themoviedb_id, $episode->season->number, $episode->number), [
			'language' => $episode->season->show->language->iso,
		]);
	}

	public function syncShow($show)
	{
		Yii::info("Syncing tv show {$show->id}...", 'application\sync');

		$attributes = $this->getShow($show);

		if ($attributes == false) {
			Yii::error("Could not get attributes from api for show {$show->id}...", 'application\sync');

			return false;
		}

		$show->attributes = (array) $attributes;

		if (is_array($attributes->created_by)) {
			foreach ($attributes->created_by as $creatorAttributes) {
				$person = Person::findOne($creatorAttributes->id);

				if ($person === null) {
					$person = new Person;
					$person->id = $creatorAttributes->id;
					$person->attributes = (array) $creatorAttributes;
					$person->save();

					$show->link('creators', $person);
					continue;
				}

				if (!ShowCreator::find()->where(['person_id' => $person->id, 'show_id' => $show->id])->exists())
					$show->link('creators', $person);
			}
		}

		if (is_array($attributes->episode_run_time)) {
			foreach ($attributes->episode_run_time as $minutes) {
				$runtime = ShowRuntime::findOne([
					'show_id' => $show->id,
					'minutes' => $minutes,
				]);

				if ($runtime === null) {
					$runtime = new ShowRuntime;
					$runtime->minutes = $minutes;
					$runtime->save();

					$show->link('runtimes', $runtime);
				}
			}
		}

		if (is_array($attributes->genres)) {
			foreach ($attributes->genres as $genreAttributes) {
				$genre = Genre::findOne($genreAttributes->id);

				if ($genre === null) {
					$genre = new Genre;
					$genre->id = $genreAttributes->id;
					$genre->attributes = (array) $genreAttributes;
					$genre->save();

					$show->link('genres', $genre);
					continue;
				}

				if (!ShowGenre::find()->where(['genre_id' => $genre->id, 'show_id' => $show->id])->exists())
					$show->link('genres', $genre);
			}
		}

		if (is_array($attributes->networks)) {
			foreach ($attributes->networks as $networkAttributes) {
				$network = Network::findOne($networkAttributes->id);

				if ($network === null) {
					$network = new Network;
					$network->id = $networkAttributes->id;
					$network->attributes = (array) $networkAttributes;
					$network->save();

					$show->link('networks', $network);
					continue;
				}

				if (!ShowNetwork::find()->where(['network_id' => $network->id, 'show_id' => $show->id])->exists())
					$show->link('networks', $network);
			}
		}

		if (is_array($attributes->origin_country)) {
			foreach ($attributes->origin_country as $countryName) {
				$country = Country::findOne([
					'name' => $countryName,
				]);

				if ($country === null) {
					$country = new Country;
					$country->name = $countryName;
					$country->save();

					$show->link('countries', $country);
					continue;
				}

				if (!ShowCountry::find()->where(['country_id' => $country->id, 'show_id' => $show->id])->exists())
					$show->link('countries', $country);
			}
		}

		if (is_array($attributes->seasons)) {
			foreach ($attributes->seasons as $seasonAttributes) {
				$season = Season::findOne([
					'show_id' => $show->id,
					'number' => $seasonAttributes->season_number,
				]);

				if ($season === null) {
					$season = new Season;
					$season->attributes = (array) $seasonAttributes;
					$season->number = $seasonAttributes->season_number;
					$season->save();

					$show->link('seasons', $season);
					continue;
				}

				if (!Season::find()->where(['number' => $season->number, 'show_id' => $show->id])->exists())
					$show->link('seasons', $season);
			}
		}

		if (isset($attributes->credits->cast) && is_array($attributes->credits->cast)) {
			foreach ($attributes->credits->cast as $castAttributes) {
				$cast = ShowCast::findOne($castAttributes->id);

				if ($cast === null) {
					$cast = new ShowCast;
					$cast->attributes = (array) $castAttributes;
					$cast->save();

					$show->link('cast', $cast);
					continue;
				}

				if (!ShowCast::find()->where(['id' => $cast->id])->exists())
					$show->link('cast', $cast);
			}
		}

		if (isset($attributes->credits->crew) && is_array($attributes->credits->crew)) {
			foreach ($attributes->credits->crew as $crewAttributes) {
				$crew = ShowCrew::findOne($crewAttributes->id);

				if ($crew === null) {
					$crew = new ShowCrew;
					$crew->attributes = (array) $crewAttributes;
					$crew->save();

					$show->link('crew', $crew);
					continue;
				}

				if (!ShowCrew::find()->where(['id' => $crew->id])->exists())
					$show->link('crew', $crew);
			}
		}

		if (!$show->save()) {
			Yii::warning("Could update tv show {$show->id} '" . $show->errors . "': " . serialize($attributes), 'application\sync');
			return false;
		}

		return true;
	}

	public function syncSeason($season)
	{
		Yii::info("Syncing tv show season {$season->id}...", 'application\sync');

		$attributes = $this->getSeason($season);

		if ($attributes == false) {
			Yii::error("Could not get attributes from api for season {$season->id}...", 'application\sync');

			return false;
		}

		if (is_array($attributes->episodes)) {
			foreach ($attributes->episodes as $episodeAttributes) {
				$episode = Episode::findOne([
					'season_id' => $season->id,
					'number' => $episodeAttributes->episode_number,
				]);

				if ($episode === null) {
					$episode = new Episode;
					$episode->attributes = (array) $episodeAttributes;
					$episode->number = $episodeAttributes->episode_number;
					$episode->save();

					$season->link('episodes', $episode);
					continue;
				}

				if (!Episode::find()->where(['number' => $episode->number, 'season_id' => $season->id])->exists())
					$season->link('episodes', $episode);
			}
		}

		$season->attributes = (array) $attributes;
		$season->themoviedb_id = $attributes->id;

		if (!$season->save()) {
			Yii::warning("Could update tv show season {$season->id} '" . $season->errors . "': " . serialize($attributes), 'application\sync');
			return false;
		}

		return true;
	}
}