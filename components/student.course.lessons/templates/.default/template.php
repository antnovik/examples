<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>

<?if(!$arResult['AUTHORIZED']):?>

<div class="mb-5 text-center">
    <h3><?=GetMessage("NOT_AUTHORIZED");?></h3>
</div>

<?else:?>
<?$closeLessons = $showStartDate = false;?>
<div class="container-fluid">
	<?if($arResult['TIME_BEFORE_START']['TIME_FORMATED']):?>
		<?$closeLessons = $showStartDate = true;?>
		<div class="col-xl-10 offset-xl-1 active-courses__inner">
			<div class="active-courses__congrat soon-course__block">
				<div>
					<p class="soon-course__tostart">До старта обучения осталось:</p>
					<p class="soon-course__time">
						<?=$arResult['TIME_BEFORE_START']['TIME_FORMATED'];?>
					</p>
				</div>					
			</div>
		</div>	
	<?endif;?>

	<div class="col-xl-10 offset-xl-1 col-sm-10 offset-sm-1 active-courses__inner">
		<?if(!$arResult['TIME_LEFT']):?>
		<?$closeLessons = true;?>
		<div class="active-courses__congrat">
			<h2>Поздравляем с успешным завершением курса!</h2>

			<?if($arResult['IS_FORM_ACTIVE']):?>
			<h3>Заполните небольшую анкету для получения свидетельства</h3>
			<a href="/student/group/<?=$arResult['GROUP_ID']?>/form/">Перейти к анкете</a>
			<?endif;?>
		</div>
		<?else:?>
		<div class="plumb" style="margin-bottom:60px"></div>
		<?endif;?>

		<?foreach($arResult['COURSE']['LESSONS'] as $lessonId => $arLesson):?>

		<div class="active-courses__onepart<?=$closeLessons?' soon-course__onepart':'';?>">
			<div>
				<?$link = '/student/group/'.$arResult['GROUP_ID'].'/lesson/'.$lessonId.'/'?>

				<h2 class="<?=$closeLessons?'opacity-5':'';?>">
					<a href="<?=$closeLessons?'javascript:void(0);':$link;?>">
						<?=$arLesson['NAME'];?>
					</a>
				</h2>

				<div class="active-courses__icons<?=$closeLessons?' from-768 opacity-5':'';?>">
					<div>
						<img src="<?=SITE_TEMPLATE_PATH?>/img/icon-time.png">
						<p><span><?=$arLesson['VIDEO_COUNT'];?> <?=$arLesson['VIDEO_COUNT_TEXT'];?></span><!--14:58--></p>
					</div>
					<div>
						<img src="<?=SITE_TEMPLATE_PATH?>/img/icon-files.png">
						<p><?=$arLesson['FILES_COUNT'];?> <span><?=$arLesson['FILES_COUNT_TEXT'];?></span></p>
					</div>
					<div>
						<img src="<?=SITE_TEMPLATE_PATH?>/img/icon-comment.png">
						<p><?=$arLesson['COMMENTS_COUNT'];?> <span><?=$arLesson['COMMENTS_COUNT_TEXT'];?></span></p>
					</div>
				</div>
				<?if($showStartDate):?>
				<p class="part-course__righttext to-768">
					Доступ к уроку откроется:
					<br><br>
					<?=$arLesson['START_DATE'];?> МСК
				</p>
				<?endif;?>
			</div>

			<?if($showStartDate):?>
			<p class="part-course__righttext from-768">
				Доступ к уроку откроется:
				<br><br>
				<?=$arLesson['START_DATE'];?> МСК
			</p>
			<?endif;?>

			<?if($arLesson['PASSED']):?>
			<img src="<?=SITE_TEMPLATE_PATH?>/img/ok.png">
			<?endif;?>
		</div>
		<?endforeach;?>	
	</div>
</div>
<?endif;?>