@extends('layouts.master')
	
	@section('content')
	@parent
	<div class="row bg-info text-white">
		<div class="mt-2 mx-auto">
		<h2>Каталог товаров</h2>
		</div>
	</div>
	<div class="row my-3">
		<div class="col-1 ml-5 font-weight-bold">
			Сортировка:
		</div>
		<div class = "col-2">
			<a href="?sorting=name-asc">По названию А-Я</a>
		</div>
		<div class = "col-2">
			<a href="?sorting=name-desc">По названию Я-А</a>
		</div>
		<div class = "col-2">
		<a href="?sorting=price-asc">По возрастанию цены</a>
		</div>
		<div class = "col-2">
		<a href="?sorting=price-desc">По убыванию цены</a>
		</div>
	</div>
	<div class="row">
	<table class="table col-11 mx-auto">
	  <thead>
		<tr>
		  <th scope="col">Название</th>
		  <th scope="col">Описание</th>
		  <th scope="col">Изображение</th>
		  <th scope="col">Цена</th>
		  <th scope="col">Наличие</th>
		   <th scope="col"></th>
		</tr>
	  </thead>
	  <tbody>
		@foreach ($prodList as $index => $prod)
			<tr>
			  <td>{{ $prod->name }}</td>
			  <td class='w-50'>{{ $prod->description }}</td>
			  <td class='w-10'><img src="{{ $prod->picture }}" class="img-fluid"/></td>
			  <td>{{ $prod->price }}</td>
			  
				@if ($prod->presence)
					<td>Есть</td>
					@if ($inCart[$index])
					<td><button type="button" attr-id="{{ $prod->id }}" attr-action="del" class="btn btn-warning to-cart">Убрать из корзины</button></td>
					@else
					<td><button type="button" attr-id="{{ $prod->id }}" attr-action="add" class="btn btn-info to-cart">Добавить в корзину</button></td>
					@endif
				@else
					<td>Нет</td>
					<td></td>
				@endif
			</tr>
		@endforeach
	  </tbody>
	</table>
	

	</div>
	
	<div class="row mt-3">
	<nav aria-label="Page navigation" class="mx-auto">
	  <ul class="pagination">
		<?for($page = 1; $page <= $pageNum; $page++):?>
	  
			<?if($page == $curPage){
				$class = 'page-item active';
			}else{
				$class = 'page-item';
			}?>
		
			<li class="<?=$class;?>"><a class="page-link" href="?sorting=<?=$sortType;?>&page=<?=$page;?>"><?=$page;?></a></li>
		<?endfor;?>
	  </ul>
	</nav>
	</div>

	@stop

	@section('scripts')
	<script>
	$(function() {
		$('.to-cart').click(function(){
			button = $(this);
			id = $(this).attr('attr-id');
			action = $(this).attr('attr-action');
			$.ajax({
				url: '/cart/ajax',
				type: "POST",
				data: {
					id: id,
					action: action
				},
				headers: {'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')},
				success: function (data) {
					console.log(data);
					
					if(data){
						console.log(action);
						console.log(button);
						
						if(action == 'add'){
							button.removeClass('btn-info')
								.addClass('btn-warning')
								.attr('attr-action', 'del')
								.html('Убрать из корзины');

						}
						else if(action == 'del'){
							button.removeClass('btn-warning')
								.addClass('btn-info')
								.attr('attr-action', 'add')
								.html('Добавить в корзину');
						}
					}
				
				},
				error: function (msg, text) {
					console.log('Ошибка ajax-запроса');
					console.log(text);
				}
			});
		});
	})
	</script>
	@stop