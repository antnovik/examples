<?php
namespace App\Services;
  
class Cart
{
    public $arCart;
	public $cartEmpty = true;
	
	public function memorise(){
		session(['cart'=>$this->arCart]);
	}
	
	public function isInCart($id){
		if($this->cartEmpty){
			return false;
		}else{
			$arCart =  $this->arCart;
			if(empty($arCart[$id])){
				return false;
			}else{
				return true;
			}
		}
	}
	
	public function addToCart($id){
		$arCart =  $this->arCart;
		if($this->isInCart($id)){
			$arCart[$id]++;
		}else{
			$arCart[$id] = 1;
		}
		$this->arCart = $arCart;
		$this->memorise();
    }
	
	public function deleteFromCart($id){
		if($this->isInCart($id)){
			$arCart =  $this->arCart;
			unset ($arCart[$id]);
			$this->arCart = $arCart;
			$this->memorise();
			if(empty($arCart)) $this->cartEmpty = true;
		}	
    }

	
	function __construct(){
		$sessionCart = session('cart');
		if(empty($sessionCart)){
			$this->arCart = array();
		}else{
			$this->arCart = $sessionCart;
			$this->cartEmpty = false;
		}
	}
}