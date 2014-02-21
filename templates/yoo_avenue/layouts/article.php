<article class="uk-article tm-article" <?php if ($permalink) echo 'data-permalink="'.$permalink.'"'; ?>>

	<?php if ($image && $image_alignment == 'none') : ?>
	<div class="tm-article-featured-image">
		<?php if ($url) : ?>
			<a href="<?php echo $url; ?>" title="<?php echo $image_caption; ?>"><img src="<?php echo $image; ?>" alt="<?php echo $image_alt; ?>"></a>
		<?php else : ?>
			<img src="<?php echo $image; ?>" alt="<?php echo $image_alt; ?>">
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div class="tm-article-content <?php echo ($date ? ' tm-article-date-true' : '') ?>">

		<?php if ($date) : ?>
		<div class="tm-article-date">
			<?php $date = printf('<span class="tm-article-date-day">'.JHtml::_('date', $date, JText::_('d M')).'</span>'.'<span class="tm-article-date-year">'.JHtml::_('date', $date, JText::_('Y')).'</span>'); ?>
		</div>
		<?php endif; ?>

		<?php if ($title) : ?>
		<h1 class="uk-article-title">
			<?php if ($url) : ?>
				<a href="<?php echo $url; ?>" title="<?php echo $title; ?>"><?php echo $title; ?></a>
			<?php else : ?>
				<?php echo $title; ?>
			<?php endif; ?>
		</h1>
		<?php endif; ?>

		<?php echo $hook_aftertitle; ?>

		<?php if ($author || $category) : ?>
		<p class="uk-article-meta">

			<?php

				$author   = ($author && $author_url) ? '<a href="'.$author_url.'">'.$author.'</a>' : $author;
				$category = ($category && $category_url) ? '<a href="'.$category_url.'">'.$category.'</a>' : $category;

				if($author) {
					printf(JText::_('TPL_WARP_META_AUTHOR'), $author);
				}

				if ($category) {
					echo ' ';
					printf(JText::_('TPL_WARP_META_CATEGORY'), $category);
				}

			?>

		</p>
		<?php endif; ?>

		<?php if ($image && $image_alignment != 'none') : ?>
			<?php if ($url) : ?>
				<a class="uk-align-<?php echo $image_alignment; ?>" href="<?php echo $url; ?>" title="<?php echo $image_caption; ?>"><img src="<?php echo $image; ?>" alt="<?php echo $image_alt; ?>"></a>
			<?php else : ?>
				<img class="uk-align-<?php echo $image_alignment; ?>" src="<?php echo $image; ?>" alt="<?php echo $image_alt; ?>">
			<?php endif; ?>
		<?php endif; ?>

		<?php echo $hook_beforearticle; ?>

		<div>
			<?php echo $article; ?>
		</div>

		<?php if ($tags) : ?>
		<p><?php echo JText::_('TPL_WARP_TAGS').': '.$tags; ?></p>
		<?php endif; ?>

		<?php if ($more) : ?>
		<p>
			<a class="uk-button" href="<?php echo $url; ?>" title="<?php echo $title; ?>"><?php echo $more; ?></a>
		</p>
		<?php endif; ?>

		<?php if ($edit) : ?>
		<p><?php echo $edit; ?></p>
		<?php endif; ?>

	</div>

	<?php if ($previous || $next) : ?>
	<ul class="uk-pagination">
		<?php if ($previous) : ?>
		<li class="uk-pagination-previous">
			<?php echo $previous; ?>
			<i class="uk-icon-angle-double-left"></i>
		</li>
		<?php endif; ?>

		<?php if ($next) : ?>
		<li class="uk-pagination-next">
			<?php echo $next; ?>
			<i class="uk-icon-angle-double-right"></i>
		</li>
		<?php endif; ?>
	</ul>
	<?php endif; ?>

	<?php echo $hook_afterarticle; ?>

</article>