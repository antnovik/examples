<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
	public $timestamps = false;
	public $goodsOnPage = 10;
	private $tableName = 'products';
	public $goodsCount;
	public $pageNum;
	public function getSort($field='name', $order = 'asc'){
		$sortedGoods = DB::select('select * from '.$this->tableName.' order by presence desc, '.$field.' '.$order);
		$this->goodsCount = count ($sortedGoods);
		$this->pageNum = $this->goodsCount % $this->goodsOnPage + 1;
		return $sortedGoods;
	}
	//функция генерирования страницы товаров (с сортировкой)
	public function getSortPage($field='name', $order = 'asc', $pagination = 1){
		$sortedGoods = DB::select('select * from '.$this->tableName.' order by presence desc, '.$field.' '.$order);
		$this->goodsCount = count ($sortedGoods);
		$this->pageNum = $this->goodsCount % $this->goodsOnPage + 1;
		
		function getSlice($goods, $start = 0, $end = 10){
			$array=array();
			for($index = $start; $index < $end; $index++){
				$array[] = $goods[$index];
			}
			return $array;
		}

		$start = ($pagination-1)*$this->goodsOnPage;
		if($pagination > 0 && $pagination < $this->pageNum){
			return getSlice($sortedGoods, $start, $start + $this->goodsOnPage);
		}elseif($pagination == $this->pageNum){ 
			return getSlice($sortedGoods, $start, $this->goodsCount);
		}else{
			return getSlice($sortedGoods,$this->goodsCount - 11, $this->goodsCount);
		}
	}
}
