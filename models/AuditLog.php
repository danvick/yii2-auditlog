<?php
/**
 * @package    yiisoft\yii2
 * @subpackage kcone87\yii2-auditlog
 * @author     Nikola Haralamov <lisi4ok@gmail.com>
 * @author     Ozan Topoglu <ozantopoglu@yahoo.com>
 * @author     Enock Willy <enokahoyah@gmail.com>
 * @since      2.0.6
 */

namespace kcone87\auditlog\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use app\models\User;
/**
 * This is the model class for table "auditlog".
 *
 * @property integer $id
 * @property string $model
 * @property integer $pk
 * @property string $action
 * @property string $old
 * @property string $new
 * @property string $at
 * @property string $by
 */
class AuditLog extends ActiveRecord
{
	public static function tableName()
	{
		return 'auditlog';
	}

	public function rules()
	{
		return [
			[['model', 'action','pk'], 'required'],
			[['old', 'new'], 'string'],
			[['at', 'by'], 'safe'],
			[['model', 'action'], 'string', 'max' => 255],
		];
	}

	public function attributeLabels()
	{
		return [
			'id' => Yii::t('app', 'ID'),
			'model' => Yii::t('app', 'Model'),
			'pk' => Yii::t('app', 'Primary Key'),
			'action' => Yii::t('app', 'Action'),
			'old' => Yii::t('app', 'Old Attributes'),
			'new' => Yii::t('app', 'New Attributes'),
			'at' => Yii::t('app', 'Changed At'),
			'by' => Yii::t('app', 'Changed By'),
		];
	}


	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAuthor()
	{
		 return $this->hasOne(User::className(), ['id' => 'by']);
	}


	public static function compare($model,$pk,$from=0,$to=0, $formatter=[]) {
		$model = explode('\\', $model);
		$model = end($model);
		if ($to == 0 ) $to = 9999999999;
		$logs= AuditLog::find()
			->where(['model'=>$model,'pk'=>$pk])
			->andWhere(['>=','at',$from])
			->andWhere(['<','at',$to])
			->cache(10)
			->all();

		$old = current($logs);
		$new = end($logs);

		$result = [];

		if (isset($old) && isset($new) && $new != false && $old != false) {
			$old_values = Json::decode($old['old']) ;
			$new_values = Json::decode($new['new']) ;
			
			if (!is_array($old_values)) {
				$keys=array_keys($new_values);
				$old_values=array_fill_keys($keys,'');
			}
			if (!is_array($new_values)) {
				$keys=array_keys($old_values);
				$new_values=array_fill_keys($keys,'');
			}

			$old_change = array_diff_assoc($old_values, $new_values);
			$new_change = array_diff_assoc($new_values, $old_values);


			foreach ($old_values as $key=>$value){
				if (isset($formatter[$key])) $format = $formatter[$key]; else $format = function ($value) {return $value;} ;
				if (isset ($old_change[$key]) || isset ($new_change[$key])) {
					$result[$key] ['old'] = $format($old_change[$key]);
					$result[$key] ['new'] = $format($new_change[$key]);
				//	$result[$key] = 'CHANGED: '.$old_change[$key]. ' --> '. $new_change[$key];
			} else $result[$key] = $format($value);
			}
		}
		return $result;
	}
}
