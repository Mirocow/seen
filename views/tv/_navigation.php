<?php
/**
 * @var yii\web\View $this
 */

use \yii\helpers\Url;

use \app\models\Show;
?>

<h1>
	<?php echo $title; ?>

	<small>
		<?php if ($active != 'dashboard'): ?>
			<a href="<?php echo Url::toRoute(['dashboard']); ?>">
		<?php endif; ?>
			<?php echo Yii::t('Show/Dashboard', 'Your TV Shows'); ?>
		<?php if ($active != 'dashboard'): ?>
			</a>
		<?php endif; ?>
		|
	</small>
	<small>
		<?php if ($active != 'archive'): ?>
			<a href="<?php echo Url::toRoute(['archive']); ?>">
		<?php endif; ?>
			<?php echo Yii::t('Show/Dashboard', 'Archive'); ?>
		<?php if ($active != 'archive'): ?>
			</a>
		<?php endif; ?>
		|
	</small>
	<small>
		<?php if ($active != 'popular'): ?>
			<a href="<?php echo Url::toRoute(['popular']); ?>">
		<?php endif; ?>
			<?php echo Yii::t('Show/Dashboard', 'Popular'); ?>
		<?php if ($active != 'popular'): ?>
			</a>
		<?php endif; ?>
		|
	</small>
	<small>
		<?php if ($active != 'recommend'): ?>
			<a href="<?php echo Url::toRoute(['recommend']); ?>">
		<?php endif; ?>
			<?php echo Yii::t('Show/Dashboard', 'Recommend'); ?>
			<span class="badge">
				<?php echo Show::getRecommend()->count(); ?>
			</span>
		<?php if ($active != 'recommend'): ?>
			</a>
		<?php endif; ?>
	</small>
</h1>
