<?php namespace app\models;

use \Yii;
use \yii\db\ActiveRecord;

use \app\components\TimestampBehavior;

/**
 * This is the model class for Languages.
 *
 * @property integer $id
 * @property string $iso
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property Episode[] $episodes
 * @property Movie[] $movies
 * @property Season[] $seasons
 * @property Show[] $shows
 */
class Language extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%language}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['iso'], 'required'],
			[['created_at', 'updated_at', 'deleted_at'], 'date', 'format' => 'Y-m-d H:i:s'],
			[['iso'], 'string', 'max' => 10]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('Language', 'ID'),
			'iso' => Yii::t('Language', 'ISO 639-1'),
			'created_at' => Yii::t('Language', 'Created at'),
			'updated_at' => Yii::t('Language', 'Updated at'),
			'deleted_at' => Yii::t('Language', 'Deleted at'),
		];
	}

	public function behaviors()
	{
		return [
			'timestamp' => [
				'class' => TimestampBehavior::className(),
				'attributes' => [
					ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
					ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
				],
			],
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getEpisodes()
	{
		return $this->hasMany(Episode::className(), ['language_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMovies()
	{
		return $this->hasMany(Movie::className(), ['id' => 'movie_id'])->viaTable('prod_movie_language', ['language_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSeasons()
	{
		return $this->hasMany(Season::className(), ['language_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getShows()
	{
		return $this->hasMany(Show::className(), ['id' => 'show_id'])->viaTable('prod_show_language', ['language_id' => 'id']);
	}
}
