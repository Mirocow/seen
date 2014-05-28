<?php
/**
 * @var yii\web\View $this
 */

use \yii\helpers\Html;
use \yii\helpers\Url;

$this->title[] = $person->name;
$this->title[] = Yii::t('Person/View', 'Persons');
?>

<div id="person-view">

	<div class="row">
		<div class="col-sm-4 col-md-3 col-lg-2">
			<img <?php echo $person->profileUrlLarge; ?>>
		</div>

		<div class="col-sm-8 col-md-9 col-lg-10">
			<h1><?php echo Html::encode($person->name); ?></h1>

			<?php if (!empty($person->biography)): ?>
				<p><?php echo Html::encode($person->biography); ?></p>
			<?php endif; ?>

			<?php if (count($movies) > 0): ?>
				<h2><?php echo Yii::t('Person/View', 'Movies with/from {name}', ['name' => $person->name]); ?></h2>

				<div class="row" id="person-view-movies">
					<?php foreach ($movies as $movie): ?>
						<div class="col-xs-6 col-sm-4 col-md-3 col-lg-2">
							<a href="<?php echo Url::toRoute(['movie/view', 'slug' => $movie->slug]); ?>" title="<?php echo Html::encode($movie->title); ?>">
								<img <?php echo $movie->posterUrlLarge; ?>>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if (count($shows) > 0): ?>
				<h2><?php echo Yii::t('Person/View', 'TV Shows with/from {name}', ['name' => $person->name]); ?></h2>

				<div class="row" id="person-view-shows">
					<?php foreach ($shows as $show): ?>
						<div class="col-xs-6 col-sm-4 col-md-3 col-lg-2">
							<a href="<?php echo Url::toRoute(['tv/view', 'slug' => $show->slug]); ?>" title="<?php echo Html::encode($show->name); ?>">
								<img <?php echo $show->posterUrlLarge; ?>>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
