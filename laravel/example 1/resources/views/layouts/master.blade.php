<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
		

        <!-- Styles -->
		<link href="{{asset('css/bootstrap.css')}}" rel="stylesheet">
		<link href="{{asset('css/custom.css')}}" rel="stylesheet">
    </head>
    <body>
	<div class="row header">
		<div  class="mx-auto pt-3 pb-2">
			<h1>Приложение</h1>
		</div>
	</div>
	@section('content')
	
	@show
	<div class="row footer bg-secondary py-5 mt-5">
		<div  class="mx-auto pt-3 pb-2 ">
			<h4>@Приложение</h4>
		</div>
	</div>
    </body>
	<script src="{{asset('js/jquery-3.5.1.js')}}"></script>
	<script src="{{asset('js/bootstrap.js')}}"></script>
	@yield('scripts')
</html>
