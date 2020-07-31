const {
    __
} = wp.i18n;

const {
    registerBlockType
}  = wp.blocks;

const {
    PanelBody,
    ToggleControl
} = wp.components;

const {
    RawHTML
} = wp.element;

import {
    YoutubeSearchController
} from './youtube.js';

class SearchController extends YoutubeSearchController {

    startRequest() {

       let listPart = [];
       if (this.props.attributes.showDuration || this.props.attributes.showDefinition) {
           listPart.push('contentDetails');
       }
       if (this.props.attributes.showViewCount) {
           listPart.push('statistics');
       }
       if (listPart.length) {
           listPart.unshift('id');
       }
       this.call(listPart.join(','));

    }

    renderForResponse(response) {

        const {
            className,
            attributes: {
                showPublishedAt,
                showDuration,
                showDefinition,
                showViewCount,
                usePaging
            },
            setAttributes
        } = this.props;

        const videos = response.getVideos();

        if (!videos.length) {
            return __("Geen video's gevonden", "youtube-search");
        }

        const prevPage = usePaging ? response.getPrevPage(): null;
        const nextPage = usePaging ? response.getNextPage(): null;

        return (
            <div className="youtube-search">
                <div className="youtube-search-result-grid">
                    <ul className="youtube-search-results">
                        {
                            videos.map( (video) => {
                                let videoDetails = this.renderVideoDetails(video);
                                return (
                                    <li>
                                        <a href={ video.url } target="_blank">
                                            <img src={ video.thumbnail } alt={ video.title } align="top" />
                                            <div className="youtube-search-video-details">
                                                <span dangerouslySetInnerHTML={ { __html: video.title } } />
                                                {
                                                    showPublishedAt || showPublishedAt === undefined &&
                                                    <>
                                                        <br/>
                                                        <em>Gepubliceerd: { video.publishedAt }</em>
                                                    </>
                                                }
                                                {
                                                    videoDetails &&
                                                    <>
                                                        <br/>
                                                        { videoDetails }
                                                    </>
                                                }
                                            </div>
                                        </a>
                                    </li>
                                );
                            })
                        }
                    </ul>
                    {
                        usePaging &&
                        <div className="youtube-search-paging-container">
                            <ul className="youtube-search-paging">
                                <li>
                                    {
                                        prevPage &&
                                        <a href="" onClick={ (event) => {
                                            event.preventDefault();
                                            this.saveQuery({
                                                prevPage: prevPage,
                                                nextPage: null
                                            }, jQuery(event.target));
                                        }}>
                                            { __("Vorige", "youtube-search") }
                                        </a>
                                    }
                                    {
                                        !prevPage && __("Vorige", "youtube-search")
                                    }
                                </li>
                                <li>
                                    {
                                        nextPage &&
                                        <a href="" onClick={ (event) => {
                                            event.preventDefault();
                                            this.saveQuery({
                                                prevPage: null,
                                                nextPage: nextPage
                                            }, jQuery(event.target));
                                        }}>
                                            { __("Volgende", "youtube-search") }
                                        </a>
                                    }
                                    {
                                        !nextPage && __("Volgende", "youtube-search")
                                    }
                                </li>
                            </ul>
                        </div>
                    }
                </div>
            </div>
        );

    }

    renderVideoDetails(video) {

        let videoDetails = [];
        if (this.props.attributes.showDuration && video.duration) {
            videoDetails.push(video.duration);
        }
        if (this.props.attributes.showDefinition && video.definition) {
            videoDetails.push(video.definition);
        }
        if (this.props.attributes.showViewCount && video.view_count) {
            videoDetails.push(video.view_count + ' views');
        }
        return videoDetails.join(' - ');

    }

    getExtraControls() {

        const {
            showPublishedAt,
            showDuration,
            showDefinition,
            showViewCount,
            usePaging
        } = this.props.attributes;

        return (
            <PanelBody
                title={ __("Weergave", 'youtube-search') }
                initialOpen={ true }
                className='youtube-search-sidebar'
            >
                <ToggleControl
                    label={  __("Toon datum", "youtube-search") }
                    checked={ showPublishedAt || showPublishedAt === undefined }
                    onChange={ (value) => {
                        this.setAttributes({
                            showPublishedAt: value
                        });
                    } }
                />
                <ToggleControl
                    label={  __("Toon tijdsduur", "youtube-search") }
                    checked={ showDuration }
                    onChange={ (value) => {
                        this.setAttributes({
                            showDuration: value
                        });
                    } }
                />
                <ToggleControl
                    label={  __("Toon kwaliteit", "youtube-search") }
                    checked={ showDefinition }
                    onChange={ (value) => {
                        this.setAttributes({
                            showDefinition: value
                        });
                    } }
                />
                <ToggleControl
                    label={  __("Toon aantal views", "youtube-search") }
                    checked={ showViewCount }
                    onChange={ (value) => {
                        this.setAttributes({
                            showViewCount: value
                        });
                    } }
                />
                <ToggleControl
                    label={  __("Gebruik paginering", "youtube-search") }
                    checked={ usePaging }
                    onChange={ (value) => {
                        this.setAttributes({
                            usePaging: value
                        });
                    } }
                />
            </PanelBody>
        );

    }

}

registerBlockType('youtube-search/search', {
    title: __("Youtube Zoeken", 'youtube-search'),
    icon: 'video-alt3',
    category: 'youtube-search',
    attributes: {
        maxResults: {
            type: 'integer'
        },
        query: {
            type: 'string'
        },
        order: {
            type: 'string'
        },
        publishedAfter: {
            type: 'string'
        },
        safeSearch: {
            type: 'string'
        },
        videoDefinition: {
            type: 'string'
        },
        videoDuration: {
            type: 'string'
        },
        videoType: {
            type: 'string'
        },
        showPublishedAt: {
            type: 'boolean'
        },
        showDuration: {
            type: 'boolean'
        },
        showDefinition: {
            type: 'boolean'
        },
        showViewCount: {
            type: 'boolean'
        },
        usePaging: {
            type: 'boolean'
        }
    },
    edit: SearchController
});
