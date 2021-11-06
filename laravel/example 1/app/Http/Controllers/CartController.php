<?php

namespace App\Http\Controllers;

  use Illuminate\Http\Request;
  use App\Models\Product;
  use App\Services\Cart;
  

class CartController extends Controller
{
   public function test(){
		$cart = new Cart();
		echo '<pre>';
		print_r($cart->arCart);
		echo'</pre>';
   }
   
    public function update(Request $request){
		$cart = new Cart();
		switch($request->action){
			case 'add':
				$cart->addToCart($request->id);
				break;
			case 'del':
				$cart->deleteFromCart($request->id);
				break;
		}
		return true;
    }
	
	public function product($id){
		$product = new Product();
		$prod = $product->find($id);
		echo $prod->id;
		echo "<br>";
		echo $prod->name;
		echo "<br>";
		echo $prod->price;
	}
}
