<?php

$detailProps = array(
    'showDuration' => $showDuration,
    'showDefinition' => $showDefinition,
    'showViewCount' => $showViewCount
);

?>
<?php if (!empty($result->data)): ?>
<div class="youtube-search">
    <div class="youtube-search-result-grid">
        <ul class="youtube-search-results">
            <?php foreach ($result->data as $video): ?>
            <?php $video_details = youtube_search_render_video_details($video, $detailProps); ?>
            <li>
                <a href="<?php echo $video->url; ?>" target="_blank">
                    <img src="<?php echo $video->thumbnail; ?>" alt="<?php echo $video->title; ?>" align="top" />
                    <div class="youtube-search-video-details">
                        <?php echo $video->title; ?>
                        <?php if ($showPublishedAt): ?>
                        <br/><em>Gepubliceerd: <?php echo $video->publishedAt; ?></em>
                        <?php endif; ?>
                        <?php if ($video_details): ?>
                        <br/><?php echo $video_details; ?>
                        <?php endif; ?>
                    </div>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="youtube-search-paging-container">
            <ul class="youtube-search-paging">
                <li>
                    <?php if ($result->prev_page): ?>
                        <a href="<?php echo youtube_search_build_nav_link($result->prev_page); ?>">Vorige</a>
                    <?php else: ?>
                        Vorige
                    <?php endif; ?>
                </li>
                <li>
                    <?php if ($result->next_page): ?>
                        <a href="<?php echo youtube_search_build_nav_link($result->next_page); ?>">Volgende</a>
                    <?php else: ?>
                        Volgende
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>
