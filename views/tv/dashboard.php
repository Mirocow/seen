<?php
/**
 * @var yii\web\View $this
 */

echo $this->render('_dashboard', [
	'archive' => false,
	'title' => Yii::t('Show/Dashboard', 'Your TV Shows'),
	'shows' => $shows,
]);
