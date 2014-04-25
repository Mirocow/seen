<?php namespace app\controllers;

use \Yii;
use \yii\web\Controller;
use \yii\filters\AccessControl;

use \app\models\Email;
use \app\models\EmailTo;
use \app\models\EmailAttachment;

class WebhookController extends Controller
{
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::className(),
				'only' => ['mandrill'],
				'rules' => [
					[
						'actions' => ['mandrill'],
						'allow' => true,
						'matchCallback' => function($rule, $action) {
							Yii::$app->request->enableCsrfValidation = false;

							$url = Yii::$app->request->isSecureConnection ? 'https://' : 'http://';
							$url .= $_SERVER['HTTP_HOST'];
							$url .= $_SERVER['REQUEST_URI'];

							if (!isset($_SERVER['HTTP_X_MANDRILL_SIGNATURE'])) {
								Yii::warning("Unauthorized request to {$url}! Signature missing.", 'webhook\mandrill');
							}

							$params = Yii::$app->request->post();

							$data = $url;
							ksort($params);
							foreach ($params as $key => $value) {
								$data .= $key;
								$data .= $value;
							}

							$signature = base64_encode(hash_hmac(
								'sha1',
								$data,
								Yii::$app->params['email']['mandrill']['webhook']['key'],
								true
							));

							if ($signature == $_SERVER['HTTP_X_MANDRILL_SIGNATURE']) {
								return true;
							} else {
								Yii::warning("Unauthorized request to {url}! {$signature} != {$_SERVER['HTTP_X_MANDRILL_SIGNATURE']}.", 'webhook\mandrill');
							}
						}
					],
				],
			],
		];
	}

	public function actionMandrill()
	{
		$events = json_decode(Yii::$app->request->post('mandrill_events'));

		if ($events === false) {
			Yii::error('Could not convert request to json: ' . serialize(Yii::$app->request->post('mandrill_events')), 'webhook\mandrill');
			throw new \yii\web\BadRequestHttpException;
		}

		foreach ($events as $event) {
			if ($event->event == 'inbound') {
				$email = new Email;
				$email->event = $event->event;
				$email->ts = date('Y-m-d H:i:s', (int) $event->ts);

				if (isset($event->msg->text))
					$email->text = $event->msg->text;

				if (isset($event->msg->html))
					$email->html = $event->msg->html;

				$email->from_email = $event->msg->from_email;
				if (isset($event->msg->from_name))
					$email->from_name = $event->msg->from_name;
				if (isset($event->msg->subject))
					$email->subject = $event->msg->subject;

				$email->spam_score = $event->msg->spam_report->score;

				if (!$email->save()) {
					Yii::error("Could not save email {$email->ts}: " . serialize($email->errors), 'webhook\mandrill');
					throw new \yii\web\HttpException(500, "Could not save email {$email->ts}!");
				}

				if (is_array($event->msg->to)) {
					foreach ($event->msg->to as $receiver) {
						$emailTo = new EmailTo;
						$emailTo->email_id = $email->id;
						$emailTo->to_email = $receiver[0];
						if (isset($receiver[1]))
							$emailTo->to_name = $receiver[1];

						if (!$emailTo->save()) {
							Yii::error("Could not save receiver '{$email->ts}': " . serialize($emailTo->errors), 'webhook\mandrill');
							throw new \yii\web\HttpException(500, "Could not save receiver for email '{$email->ts}'!");
						}
					}
				}

				if (isset($event->msg->attachments)) {
					$attachments = (array) $event->msg->attachments;
					foreach ($attachments as $key => $attachment) {
						$emailAttachment = new EmailAttachment;
						$emailAttachment->email_id = $email->id;
						$emailAttachment->name = $attachment->name;
						$emailAttachment->type = $attachment->type;

						if ($emailAttachment->save()) {
							$emailAttachment->saveAttachment($attachment->content, $attachment->base64);
						} else {
							Yii::error("Could not save attachment '{$attachment->name}': " . serialize($emailAttachment->errors), 'webhook\mandrill');
							throw new \yii\web\HttpException(500, "Could not save attachment for email '{$email->ts}'!");
						}
					}
				} else {
					Yii::error('No attachments: ' . isset($event->msg->attachments));
					if (isset($event->msg->attachments)) {
						Yii::error('Type: ' . gettype($event->msg->attachments));
						Yii::error('Attachments: ' . serialize($event->msg->attachments));
					} else {
						Yii::error('Msg: ' . serialize($event->msg));
					}

					Yii::warning('Json: ' . Yii::$app->request->post('mandrill_events'));
				}

				if (isset($event->msg->images)) {
					$images = (array) $event->msg->images;

					foreach ($images as $key => $attachment) {
						$emailAttachment = new EmailAttachment;
						$emailAttachment->email_id = $email->id;
						$emailAttachment->name = $attachment->name;
						$emailAttachment->type = $attachment->type;

						if ($emailAttachment->save()) {
							$emailAttachment->saveAttachment($attachment->content, $attachment->base64);
						} else {
							Yii::error("Could not save image attachment '{$attachment->name}': " . serialize($emailAttachment->errors), 'webhook\mandrill');
							throw new \yii\web\HttpException(500, "Could not save image attachment for email '{$email->ts}'!");
						}
					}
				} else {
					Yii::error('No image attachments: ' . isset($event->msg->images));
					if (isset($event->msg->images)) {
						Yii::error('Type: ' . gettype($event->msg->images));
						Yii::error('Attachments: ' . serialize($event->msg->images));
					} else {
						Yii::error('Msg: ' . serialize($event->msg));
					}

					Yii::warning('Json: ' . Yii::$app->request->post('mandrill_events'));
				}
			} else {
				Yii::warning("Unknown event '{$event->event}': " . serialize($event), 'webhook\mandrill');
			}
		}
	}
}
