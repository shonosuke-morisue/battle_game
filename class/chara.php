<?php
//----------------------------------------
//
// キャラ情報取得クラス
//
//----------------------------------------

class chara{
	public $chara_data;                     // キャラデータ
	public $wepon_data;                     // 武器のデータ 
	public $head_data;                      // 頭防具のデータ
	public $body_data;                      // 胴防具のデータ
	public $arm_data;                       // 腕防具のデータ
	public $waist_data;                     // 腰防具のデータ
	public $leg_data;                       // 脚防具のデータ
	public $skill_category_list = array();  // 装備品によるポイントが発生しているスキルのカテゴリーリスト
	public $active_skill_list = array();    // 装備品によって発動しているスキルのリスト
	public $body_double_count;              // 胴系統倍化の倍率
	public $body_double_count_before;       // 計算用のテンポラリーな胴系統倍化の倍率
	
	
	//--------------------------------------------
	// キャラ情報初期化処理
	//--------------------------------------------
	function __construct($chara_id) {
		// DBからキャラ情報を取得
		$db_link = db_access();
		$sth = $db_link->prepare('SELECT * FROM charactor WHERE id = :chara_id');
		$sth->bindValue(':chara_id' , $chara_id , PDO::PARAM_INT);
		$sth->execute();

		$this->chara_data = $sth->fetch();

		// 装備品の情報を取得
		$wear_type = array( head , arm , waist , leg , body ); // 胴系統倍化のため、bodyは最後に回す
		foreach ($wear_type as $type) {
			$sql = 'SELECT * FROM '.$type.'_data WHERE id = :wear_id ';
			$sth = $db_link->prepare( $sql );
			$sth->bindValue(':wear_id', $this->chara_data['equip_'.$type] , PDO::PARAM_INT);
			$sth->execute();
			$this->{$type.'_data'} = $sth->fetch();
			
			// 装備品から発動スキルを取得
			$this->get_skill( null , $this->{$type.'_data'} ,$type );
			$this->body_double_count_before = $this->body_double_count;
		}
		
		// DB切断処理
		db_close($db_link);
	}

	//--------------------------------------------
	// 武器装備処理
	//--------------------------------------------
	function equip_weapon( $weapon_id ) {
		;
	}

	//--------------------------------------------
	// 防具装備処理
	//--------------------------------------------
	
	// 複数の防具をまとめて付け替える場合はこちらを使用
	// 胴系統倍化の処理を正しく行うため
	// $wear_type にはキーに装備品のタイプ名、値に装備品のIDを入れた連想配列を渡す
	function equip_wear_all($wear_type) {
		$this->body_double_count_before = $this->body_double_count;
		foreach ($wear_type as $key => $value) {
			if ($value !== null && $chara->{$key.'_data'}['id'] !== $value) {
				$this->equip_wear($key, $value);
			};
		}
	}
	
	// 一つだけ装備を変更する時はこちらでもOK
	function equip_wear( $wear_type , $wear_id) {
		
		// DBに接続
		$db_link = db_access();

		// 外す装備品の情報を取得
		$remove_wear_data = $this->{$wear_type.'_data'};

		// 装備する装備品の情報を取得
		$sql = 'SELECT * FROM '.$wear_type.'_data WHERE id = :wear_id ';
		$sth = $db_link->prepare( $sql );
		$sth->bindValue(':wear_id', $wear_id , PDO::PARAM_INT);
		$sth->execute();
		$equip_wear_data = $sth->fetch();

		$this->{$wear_type.'_data'} = $equip_wear_data;
		
		// 装備パラメータを反映
		$this->chara_data['def']         = $this->chara_data['def']         - $remove_wear_data['def'] + $equip_wear_data['def'];
		$this->chara_data['def_fire']    = $this->chara_data['def_fire']    - $remove_wear_data['def_fire'] + $equip_wear_data['def_fire'];
		$this->chara_data['def_water']   = $this->chara_data['def_water']   - $remove_wear_data['def_water'] + $equip_wear_data['def_water'];
		$this->chara_data['def_thunder'] = $this->chara_data['def_thunder'] - $remove_wear_data['def_thunder'] + $equip_wear_data['def_thunder'];
		$this->chara_data['def_ice']     = $this->chara_data['def_ice']     - $remove_wear_data['def_ice'] + $equip_wear_data['def_ice'];
		$this->chara_data['def_dragon']  = $this->chara_data['def_dragon']  - $remove_wear_data['def_dragon'] + $equip_wear_data['def_dragon'];


		// 装備した後のパラメータをDBに反映
		$id = $this->chara_data['id'];
		$sql = 'UPDATE charactor SET
				equip_'.$wear_type.' = :wear_id ,
				def                  = :def ,
				def_fire             = :def_fire ,
				def_water            = :def_water ,
				def_thunder          = :def_thunder ,
				def_ice              = :def_ice ,
				def_dragon           = :def_dragon
			WHERE
				id = '.$id;

		$sth = $db_link->prepare( $sql );
		$sth->bindValue(':wear_id'     , $wear_id                 , PDO::PARAM_INT);
		$sth->bindValue(':def'         , $this->chara_data['def']         , PDO::PARAM_INT);
		$sth->bindValue(':def_fire'    , $this->chara_data['def_fire']    , PDO::PARAM_INT);
		$sth->bindValue(':def_water'   , $this->chara_data['def_water']   , PDO::PARAM_INT);
		$sth->bindValue(':def_thunder' , $this->chara_data['def_thunder'] , PDO::PARAM_INT);
		$sth->bindValue(':def_ice'     , $this->chara_data['def_ice']     , PDO::PARAM_INT);
		$sth->bindValue(':def_dragon'  , $this->chara_data['def_dragon']  , PDO::PARAM_INT);
		$sth->execute();

		// スキルを取得
		echo '[[equip_wear]]-['.$wear_type.']';
		$this->get_skill($remove_wear_data , $equip_wear_data , $wear_type);
		
		// DB切断処理
		db_close($db_link);
		
		// 装備変更メッセージ
		if ($remove_wear_data["id"] !== $equip_wear_data['id']) {
			echo $this->equip_complete_msg.$wear_type.'を['.$remove_wear_data["name"].']から['.$equip_wear_data['name'].']に変更しました。<br>';
		}
	}

	//--------------------------------------------
	// スキル取得処理
	//--------------------------------------------
	function get_skill( $remove_data , $equip_data , $equip_type ) {
		// 装備品からスキルポイント取得
		for ($i = 1; $i <= 5; $i++) {
			// 外す装備のスキルパラメータを反映
			if ($remove_data !== null && $remove_data['skill_0'.$i] !== "") {
				
				$skill_category = $remove_data['skill_0'.$i];
				$skill_point= $remove_data['skill_point_0'.$i];
				
				if ($skill_category == '胴系統倍化') {
					$this->body_double_count += -1;
				}
				
				// 胴系統倍化の倍化処理
				if ( $equip_type == 'body') {
					$skill_point = $skill_point * ( $this->body_double_count_before + 1 );
				}
				
				if (array_key_exists($skill_category, $this->skill_category_list)) {
					$this->skill_category_list[$skill_category] += -($skill_point);
					if ($this->skill_category_list[$skill_category] == 0) {
						unset($this->skill_category_list[$skill_category]);
					}
				}else{
					$wear_type +=  array( $skill_category => -($skill_point));
				}
			}
			
			// 新たな装備のスキルパラメータを反映
			if ($equip_data['skill_0'.$i] !== "") {
			
				$skill_category = $equip_data['skill_0'.$i];
				$skill_point= $equip_data['skill_point_0'.$i];
			
				if ($skill_category == '胴系統倍化') {
					$this->body_double_count += 1;
				}
				
				// 胴系統倍化の倍化処理
				if ( $equip_type == 'body') {
					$skill_point = $skill_point * ( $this->body_double_count + 1 );
				}
				
				if (array_key_exists($skill_category, $this->skill_category_list)) {
					$this->skill_category_list[$skill_category] += $skill_point;
				}else{
					$this->skill_category_list +=  array($skill_category => $skill_point);
				}
			}
		}

		
// 		echo '['.$equip_type.']';
// 		dump_html($this->skill_category_list);
// 		echo '<hr>';
		
		// $active_skill_list を初期化
		// 前のスキルの情報を残さないように初期化してから入れなおす
		$this->active_skill_list = array();
		
		
		// 発動しているスキルを取得
		foreach ($this->skill_category_list as $category_name => $skill_point) {
						
			// foreach内で繰り返す使用する変数の初期化
			$skill_list = array();
			$active_skill;

			// DBに接続
			$db_link = db_access();
			
			// 装備する装備品の情報を取得
			$sql = 'SELECT * FROM skill_data WHERE category = :category ';
			$sth = $db_link->prepare( $sql );
			$sth->bindValue(':category', $category_name , PDO::PARAM_STR);
			$sth->execute();
			
			while ($skill_data = $sth->fetch()) {
				$skill_list[] = $skill_data;
			}

// 			debug用出力　ここから
// 			echo "[[ skill_list ]] =><br>";
// 			foreach ($skill_list as $value) {
// 				echo $value['name'].'['.$value['skill_point'].']<br>';
// 			}
// 			debug用出力　ここまで

			// $skill_listに複数の値が入っている場合のみsort処理を行う
			if (count($skill_list) > 1) {
				$point = array();
				foreach ($skill_list as $value) {
					$point[]  = $value['skill_point'];
				}
				array_multisort( $point , SORT_ASC , SORT_NUMERIC , $skill_list);
			}
				
// 			　debug用出力　ここから
// 			echo "[[ skill_list ]] ソート後 =><br>";
// 			foreach ($skill_list as $value) {
// 				echo $value['name'].'['.$value['skill_point'].']<br>';
// 			}
// 			　debug用出力　ここまで
			
				
			
			// プラススキルの発動チェック
			
			foreach ($skill_list as $skill) {

				//			debug用出力　ここから
				//dump_html($skill);
// 				echo $skill['name'].'-';
// 				if ($skill_point > 0 ) {
// 					echo "条件1:クリア-";
// 				}else {
// 					echo "条件1:NG-";
// 				}
// 				if ($skill_point > $skill['skill_point'] ) {
// 					echo "条件2:クリア-";
// 				}else {
// 					echo "条件2:NG-";
// 				}
// 				if ($skill['skill_point'] > 0 ) {
// 					echo "条件3:クリア<br>";
// 				}else {
// 					echo "条件3:NG<br>";
// 				}
// 				echo '<hr>';
				//			debug用出力　ここまで
				
				if ($skill_point > 0 && $skill_point >= $skill['skill_point'] && $skill['skill_point'] > 0) {
					$active_skill = $skill;
				}
			}
			
			array_reverse($skill_list);

			// マイナススキルの発動チェック
			foreach ($skill_list as $skill) {
				if ($skill_point < 0 && $skill_point <= $skill['skill_point'] && $skill['skill_point'] < 0) {
					$active_skill = $skill;
				}
			}
			if (!($active_skill == null) && !(in_array($active_skill, $this->active_skill_list))) {
				$this->active_skill_list[] = $active_skill;
			}				
		}
	}
}
?>