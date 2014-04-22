<?php
/**
 * @var yii\web\View $this
 */

use \yii\helpers\Html;
use \yii\helpers\Url;
?>

<h1>
	<div class="clearfix">
		<div class="pull-left"><?php echo Html::encode($movie->title); ?></div>
		<div class="pull-right"><a href="<?php echo Url::toRoute(['watch', 'slug' => $movie->slug]); ?>" class="btn btn-sm <?php if (count($userMovies) === 0): ?>btn-primary<?php else: ?>btn-default<?php endif; ?>"><?php echo Yii::t('Movie/View', 'Watched'); ?></a></div>
</h1>

<div id="movie-view">
	<div class="row">
		<div class="col-sm-4">
			<img src="<?php echo $movie->posterUrlLarge; ?>">
		</div>

		<div class="col-sm-8">
			<?php if (!empty($movie->overview)): ?>
				<div id="movie-view-overview">
					<?php echo Html::encode($movie->overview); ?>
				</div>
			<?php endif; ?>

			<?php if (count($userMovies)): ?>
				<div id="movie-view-watched">
					<h2><?php echo Yii::t('User', 'Watched {count} times', ['count' => count($userMovies)]); ?></h2>

					<ul id="movie-view-watched-list" class="list-unstyled list-inline">
						<?php foreach ($userMovies as $userMovie): ?>
							<li title="<?php echo date(Yii::$app->params['lang'][Yii::$app->language]['datetime'], strtotime($userMovie->created_at)); ?>"><?php echo date(Yii::$app->params['lang'][Yii::$app->language]['date'], strtotime($userMovie->created_at)); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if (count($movie->cast)): ?>
				<div id="movie-view-cast-wrapper" class="persons">
					<h2><?php echo Yii::t('Movie/View', 'Cast'); ?></h2>

					<ul id="movie-view-cast" class="list-unstyled list-inline">
						<?php foreach ($movie->cast as $cast): ?>
							<li>
								<img src="<?php echo $cast->profileUrl; ?>" alt="<?php echo Html::encode($cast->name); ?>" title="<?php echo Html::encode($cast->name); ?>">
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if (count($movie->crew)): ?>
				<div id="movie-view-crew-wrapper" class="persons">
					<h2><?php echo Yii::t('Movie/View', 'Crew'); ?></h2>

					<ul id="movie-view-crew" class="list-unstyled list-inline">
						<?php foreach ($movie->crew as $crew): ?>
							<li>
								<img src="<?php echo $crew->profileUrl; ?>" alt="<?php echo Html::encode($crew->name); ?>" title="<?php echo Html::encode($crew->name); ?>">
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if (count($movie->similarMovies)): ?>
				<div id="movie-view-similar-wrapper" class="persons">
					<h2><?php echo Yii::t('Movie/View', 'similar'); ?></h2>

					<ul id="movie-view-similar" class="list-unstyled list-inline">
						<?php foreach ($movie->similarMovies as $similarMovie): ?>
							<li>
								<img src="<?php echo $similarMovie->posterUrlSmall; ?>" alt="<?php echo Html::encode($similarMovie->title); ?>" title="<?php echo Html::encode($crew->title); ?>">
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>