<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>

<?if(!$arResult['AUTHORIZED']):?>
<div class="mb-5 text-center">
    <h3><?=GetMessage("NOT_AUTHORIZED");?></h3>
</div>

<?elseif(!$arResult['TIME_LEFT']):?>
<div class="mb-5 text-center">
    <h3>Срок доступа к курсу истек.</h3>
</div>

<?elseif($arResult['IS_WAITING']):?>
<div class="mb-5 text-center">
    <h3>Курс еще не начался.</h3>
</div>

<?else:?>

<div class="container-fluid">
	<div class="col-12 offset-xl-1-- lesson-page__number" style="">
		<?if($arResult['PREV_LESSON']['ID']):?>
			<?$link = '/student/group/'.$arResult['GROUP_ID'].'/lesson/'.$arResult['PREV_LESSON']['ID'].'/';?>
			<a href="<?=$link;?>" class="lesson-page__prev"><img src="<?=SITE_TEMPLATE_PATH?>/img/prev-les.png"> <span class="from-992">Предыдущий</span> <span class="from-1200">урок</span></a>
		<?endif;?>
				
		<h2 class="content__zagolovok lesson-page__zagolovok w-50" >
			<?if($arResult['LESSON']['ALT_NAME']):?>
				Урок <?=$arResult['LESSON']['ORDER'];?>: <?=$arResult['LESSON']['ALT_NAME'];?>
			<?else:?>
				<?=$arResult['LESSON']['NAME'];?>
			<?endif;?>
		</h2>
				
		<?if($arResult['NEXT_LESSON']['ID']):?>
			<?$link = '/student/group/'.$arResult['GROUP_ID'].'/lesson/'.$arResult['NEXT_LESSON']['ID'].'/';?>
			<a href="<?=$link;?>" class="lesson-page__next"><span class="from-992">Следующий</span> <span class="from-1200">урок</span> <img src="<?=SITE_TEMPLATE_PATH?>/img/next-les.png"></a>
		<?endif;?>
	</div>

	<div class="col-xl-10 offset-xl-1 active-courses__inner">
	<?if($arResult['LESSON']['TEXT']):?>
		<div class="active-courses__onepart lesson-page__one-part">
			<div>
				<div>
					<?=$arResult['LESSON']['TEXT'];?>
				</div>
			</div>
		</div>
	<?endif;?>
	</div>

<?if($arResult['LESSON']['FILE_ROWS']):?>
	<div class="col-xl-8 offset-xl-2 col-sm-10 offset-sm-1 active-courses__inner lesson-page__inner">
		<h3 class="content__small-podzag">Файлы для скачивания</h3>
		<?foreach($arResult['LESSON']['FILE_ROWS'] as $fileRow):?>
			<div class="active-course__materials-flex">
				<?foreach($fileRow as $arFile):?>
					<a href="<?=$arFile['PATH'];?>" target="_blank">
						<div>
							<img src="<?=SITE_TEMPLATE_PATH?>/img/big-file.png">
							<div>
								<p><?=$arFile['NAME'];?></p>
								<span><?=$arFile['SIZE_TEXT'];?></span>
							</div>
						</div>
					</a>
				<?endforeach;?>
			</div>
		<?endforeach;?>
	</div>
<?endif;?>

<?if($arResult['LESSON']['VIDEO']):?>
	<div class="col-xl-8 offset-xl-2 col-sm-10 offset-sm-1 active-courses__inner lesson-page__inner" id="video-block">

	<?foreach($arResult['LESSON']['VIDEO'] as $arVideo):?>
		<?=$arVideo['code'];?>
	<?endforeach;?>

	</div>
<?endif;?>
</div>
		
<?endif;?>