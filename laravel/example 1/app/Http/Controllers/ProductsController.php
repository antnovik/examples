<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\Cart;

class ProductsController extends Controller
{
   
	public function productsList (){
		$product = new Product();
		$cart = new Cart();
		$sortField = 'name';
		$sortOrder = 'asc';
		$pagination = 1;
		if (!empty($_GET['sorting'])){
			switch($_GET['sorting']){
				case 'name-asc':
					$sortField = 'name';
					$sortOrder = 'asc';
					break;
				case 'name-desc':
					$sortField = 'name';
					$sortOrder = 'desc';
					break;
				case 'price-asc':
					$sortField = 'price';
					$sortOrder = 'asc';
					break;
				case 'price-desc':
					$sortField = 'price';
					$sortOrder = 'desc';
					break;
			}
			$sortType = $_GET['sorting'];
		}else{
			$sortType = '';
		}
		if (!empty($_GET['page'])){
			$pagination = (int)$_GET['page'];
		}
		$prodList = $product->getSortPage($sortField, $sortOrder, $pagination);
		$inCart = array();
		foreach($prodList as $index => $obProduct){
			if($cart->isInCart($obProduct->id)) $inCart[$index] = true; else $inCart[$index] = false;
		}

		
		return view('products', ['prodList' => $prodList, 'inCart'=> $inCart, 'pageNum'=> $product->pageNum, 'curPage'=>$pagination, 'sortType' => $sortType]);
	}
}