<?php if (!$embed_html): ?>
    <?php if ($post_thumbnail): ?>
<a class="post-thumbnail" href="<?php echo esc_url($youtube_url); ?>">
<?php echo $post_thumbnail; ?>
</a>
    <?php endif; ?>
<?php else: ?>
<?php echo $embed_html; ?>
<?php endif; ?><br>
<em><?php echo $duration; ?> - <?php echo $definition; ?> - <?php echo $view_count; ?> views</em>
