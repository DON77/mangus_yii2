<?php

namespace app\controllers;

use app\components\Controller;
use Abraham\TwitterOAuth\TwitterOAuth;
use app\models\Profile;
use yii\helpers\Json;
use Exception;
use yii;

class TwitterController extends Controller
{
  public function actionAddToken()
  {
    $oauth_token = Yii::$app->request->post('oauth_token');
    $oauth_token_secret = Yii::$app->request->post('oauth_token_secret');
    $errors = [];
    try {
      $data = Yii::$app->params['apps']['twitter'];
      $connection = new TwitterOAuth($data['consumerKey'], $data['consumerSecret'], $oauth_token, $oauth_token_secret);
      $user = $connection->get("account/verify_credentials");
      if (Profile::findOne([
        'p_id' => $user->id,
        'user_id' => $this->user->id,
        'type' => Profile::TYPE_TWITTER
      ])) {
        throw new Exception('Profile already in use');
      }
      $profile = new Profile();
      $profile->type = Profile::TYPE_TWITTER;
      $profile->user_id = $this->user->id;
      $profile->p_name = $user->screen_name;
      $profile->p_id = $user->id;
      $profile->accessToken = Json::encode(['oauth_token' => $oauth_token, 'oauth_token_secret' => $oauth_token_secret]);
      $profile->save();

      if ($profile->hasErrors()) {
        throw new Exception($profile->getFirstErrors());
      }
    } catch (Exception $e) {
      $errors[] = $e->getMessage();
    }
    return Json::encode([
      'success' => empty($errors),
      'data' => $errors
    ]);
  }
}