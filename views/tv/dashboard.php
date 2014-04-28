<?php
/**
 * @var yii\web\View $this
 */
$this->title[] = Yii::t('Show/Dashboard', 'Your TV Shows');

echo $this->render('_dashboard', [
	'archive' => false,
	'title' => Yii::t('Show/Dashboard', 'Your TV Shows'),
	'shows' => $shows,
]);
