<?php

namespace app\services;

use app\components\Service;
use app\components\ServiceResult;
use app\components\ServiceResultInterface;
use app\models\Connect;
use app\models\Post as PostModel;
use app\models\User;
use Exception;
use DateTime;
use app\components\beanstalkd\Beanstalkd;

class Post extends Service
{
	/**
	 * @param Connect $connect
	 * @param array $postData
	 *
	 * @return ServiceResultInterface
	 */
	public function postToConnect(Connect $connect, array $postData)
	{
		$result = new ServiceResult();
		$post = new PostModel();
		$post->userId = $connect->userId;
		$post->connectId = $connect->id;
		$post->content =  $postData['content'];
		$post->image = $postData['image'];

		$delay = 0;
		if (!empty($postData['scheduled'])) {
			try {
				$post->scheduled = (new DateTime())->setTimestamp($postData['scheduled']);
				$now = new DateTime();
				$delay = $post->scheduled->getTimestamp() - $now->getTimestamp();
			} catch (Exception $e) {

			}
		}

		if ($post->save()) {
			Beanstalkd::queue()->useTube('posts')->put($post->id, null, $delay);
			$post->refresh();

			$data = [
				'id' => $post->id,
				'content' => $post->content,
				'connect' => [
					'id' => $post->connect->id,
					'name' => $post->connect->name
				],
				'createdAt' => $post->createdAt,
				'scheduled' => $post->scheduled,
				'published' => $post->published
			];
			$result->addResult($data);
		} else {
			$result->addErrors($post);
		}
		return $result;
	}
}