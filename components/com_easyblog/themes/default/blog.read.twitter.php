<?php
/**
 * @package		EasyBlog
 * @copyright	Copyright (C) 2010 Stack Ideas Private Limited. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 *
 * EasyBlog is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */
defined('_JEXEC') or die('Restricted access');

?>
<div id="ezblog-body" itemscope itemtype="http://schema.org/Blog">
<div id="entry_<?php echo $blog->id; ?>" class="blog-read micro-twitter clearfix">
	<?php if( !empty( $notice ) ){ ?>
		<?php echo $this->fetch( 'notice.php' , array( 'notice' => $notice ) ); ?>
	<?php } ?>

	<?php if( !$ispreview ){ ?>
		<?php echo $this->fetch( 'blog.admin.tool.php' , array( 'row' => $blog ) ); ?>
	<?php } ?>

	<!-- @module: easyblog-before-entry -->
	<?php echo EasyBlogHelper::renderModule( 'easyblog-before-entry' ); ?>

	<?php if( $blog->isFeatured() ) { ?>
	<div class="blog-head mtm">
		<div class="tag-featured"><?php echo Jtext::_('COM_EASYBLOG_FEATURED_FEATURED'); ?><i></i></div>
	</div>
	<?php } ?>

	<div class="blog-brief ptl pbl">
		<div class="in clearfix">
			<?php if( $system->config->get( 'layout_avatar' ) && $this->getParam( 'show_avatar_entry' ) ){ ?>
				<!-- @template: Avatar -->
				<?php echo $this->fetch( 'blog.avatar.php' , array( 'row' => $blog , 'customClass' => 'blog-read-avatar' ) ); ?>
			<?php } ?>

			<!-- Post metadata -->
			<?php echo $this->fetch( 'blog.meta.read.php' , array( 'row' => $blog, 'postedText' => JText::_( 'COM_EASYBLOG_TWEETED' ) ) ); ?>

			<div class="blog-option mts">
				<ul class="reset-ul float-li small fsm">

					<?php echo $this->fetch( 'blog.read.fontsize.php' ); ?>

					<?php if( $this->getParam( 'show_hits' , true ) ){ ?>
						<li class="blog-hit"><?php echo JText::sprintf( 'COM_EASYBLOG_HITS_TOTAL' , $blog->hits ); ?></li>
					<?php } ?>

					<?php if( $system->config->get('main_comment') && $blog->totalComments !== false ){ ?>
					<li class="blog-comments">
						<?php if( $system->config->get('comment_disqus') ) { ?>
							<?php echo $blog->totalComments; ?>
						<?php } else { ?>
						<a href="<?php echo EasyBlogRouter::_( 'index.php?option=com_easyblog&view=entry&id=' . $blog->id ); ?>#comments"><?php echo $this->getNouns( 'COM_EASYBLOG_COMMENT_COUNT' , $blog->totalComments , true ); ?></a>
						<?php } ?>
					</li>
					<?php } ?>

					<?php if($system->config->get('main_subscription') && $blog->subscription) { ?>
					<li class="email">
						<a href="javascript:eblog.subscription.show('<?php echo EBLOG_SUBSCRIPTION_ENTRY;?>' , '<?php echo $blog->id;?>');"><?php echo JText::_('COM_EASYBLOG_SUBSCRIBE_BLOG'); ?></a>
					</li>
					<?php } ?>

					<?php echo EasyBlogHelper::getHelper( 'publishtools' )->getHTML( $blog->id ); ?>

					<?php if( $system->config->get( 'main_reporting') && ( $system->my->id > 0 || $system->my->id <= 0 && $system->config->get( 'main_reporting_guests') ) ){ ?>
					<li class="blog-report">
						<a href="javascript:void(0);" onclick="eblog.report.show( '<?php echo $blog->id;?>' , '<?php echo EBLOG_REPORTING_POST;?>' );"><?php echo JText::_( 'COM_EASYBLOG_REPORT_THIS_POST');?></a>
					</li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>

	<!-- Load social buttons -->
	<?php if( in_array( $system->config->get( 'main_socialbutton_position' ) , array( 'top' , 'left' , 'right' ) ) ){ ?>
		<?php echo EasyBlogHelper::showSocialButton( $blog ); ?>
	<?php } ?>

	<?php
	/**
	* ----------------------------------------------------------------------------------------------------------
	* End blog head. Content start here
	* ----------------------------------------------------------------------------------------------------------
	*/
	?>
	<?php echo $blog->event->afterDisplayTitle; ?>
	<div class="blog-text clearfix prel mtm mbm">



		<?php echo $blog->event->beforeDisplayContent; ?>
		<?php if(!empty($blog->toc)){ echo $blog->toc; } ?>

		<!-- @module: easyblog-before-content -->
		<?php echo EasyBlogHelper::renderModule( 'easyblog-before-content' ); ?>

		<div class="blog-tweet"><?php echo EasyBlogHelper::getHelper( 'String' )->linkTweets( EasyBlogHelper::getHelper( 'String' )->url2link( $blog->content ) ); ?></div>

		<!-- @module: easyblog-after-content -->
		<?php echo EasyBlogHelper::renderModule( 'easyblog-after-content' ); ?>

		<?php if( $system->config->get( 'main_locations_blog_entry' ) ){ ?>
		<?php echo EasyBlogHelper::getHelper( 'Maps' )->getHTML( true ,
																$blog->address,
																$blog->latitude,
																$blog->longitude ,
																$system->config->get( 'main_locations_blog_map_width') ,
																$system->config->get( 'main_locations_blog_map_height' ),
																JText::sprintf( 'COM_EASYBLOG_LOCATIONS_BLOG_POSTED_FROM' , $blog->address ),
																'post_map_canvas_' . $blog->id );?>
		<?php } ?>
		<?php echo $blog->event->afterDisplayContent; ?>

		<!-- Copyright text -->
		<?php if( $system->config->get( 'layout_copyrights' ) && !empty($blog->copyrights) ) { ?>
			<?php echo $this->fetch( 'blog.copyright.php' , array( 'row' => $blog ) ); ?>
		<?php } ?>
	</div>

	<?php if( $this->getParam( 'show_last_modified' ) ){ ?>
		<!-- Modified date -->
		<span class="blog-modified-date">
			<?php echo JText::_( 'COM_EASYBLOG_LAST_MODIFIED' ); ?>
			<?php echo JText::_( 'COM_EASYBLOG_ON' ); ?>
			<time datetime="<?php echo $this->formatDate( '%Y-%m-%d' , $row->modified ); ?>">
				<span><?php echo $this->formatDate( $system->config->get('layout_dateformat') , $row->modified ); ?></span>
			</time>
		</span>
	<?php } ?>

	<?php if( $system->config->get( 'main_ratings' ) ) { ?>
		<!-- Blog ratings -->
		<?php echo $this->fetch( 'blog.rating.php' , array( 'row' => $blog ) ); ?>
	<?php } ?>

	<?php if( $this->getParam( 'show_tags' ) ){ ?>
		<?php echo $this->fetch( 'tags.item.php' , array( 'tags' => $tags ) ); ?>
	<?php } ?>

	<?php if( $system->config->get( 'main_socialbutton_position' ) == 'bottom'){ ?>
		<?php echo EasyBlogHelper::showSocialButton($blog); ?>
	<?php } ?>

	<!-- Standard facebook like button needs to be at the bottom -->
	<?php if( $system->config->get('main_facebook_like') && $system->config->get('main_facebook_like_layout') == 'standard' ){ ?>
		<?php echo $this->fetch( 'facebook.standard.php' , array( 'facebook' => $facebookLike ) ); ?>
		<div class="clear"></div>
	<?php } ?>

	<?php echo $this->fetch( 'blog.read.navigation.php' , array( 'nextLink' => $nextLink , 'prevLink' => $prevLink ) ); ?>

</div><!--end: .blog-read-->

<?php echo $adsenseHTML; ?>

<?php echo $this->fetch( 'blog.read.tabs.php' , array( 'related' => $blogRelatedPost ) ); ?>

<?php if( $system->config->get('main_showauthorinfo') ): ?>
	<?php echo $this->fetch( 'author.info.php' ); ?>
<?php endif; ?>

<?php if( $system->config->get('main_comment') ) { ?>
	<?php echo $commentHTML;?>
<?php } ?>
</div><!--end: #ezblog-body-->
