<?php

namespace app\components;

use Exception;
use yii\db\ActiveRecord;

/**
 * Class ServiceResult
 * @package app\components
 */
class ServiceResult implements ServiceResultInterface
{
	private $result;
	private $errors = [];

	public function addResult($result = [])
	{
		$this->result = $result;
	}

	private function addError($error)
	{
		if ($error instanceof Exception) {
			$this->errors[] = $error->getMessage();
		}
		if ($error instanceof ActiveRecord) {
			foreach ($error->getFirstErrors() as $errorMsg) {
				$this->errors[] = $errorMsg;
			}
		}
		if (is_string($error)) {
			$this->errors[] = $error;
		}
	}

	public function addErrors($errors)
	{
		if (is_array($errors)) {
			foreach ($errors as $error) {
				$this->addError($error);
			}
		} else {
			$this->addError($errors);
		}
	}

	public function isSuccessful()
	{
		return empty($this->errors);
	}

	public function result()
	{
		return $this->isSuccessful() ? $this->result : $this->errors;
	}
}