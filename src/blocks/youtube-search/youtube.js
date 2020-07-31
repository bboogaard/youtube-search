const {
    __
} = wp.i18n;

const {
    BaseControl,
    DatePicker,
    PanelBody,
    RangeControl,
    SelectControl,
    TextControl
} = wp.components;

const {
    Component
} = wp.element;

const {
    InspectorControls
} = wp.blockEditor

import {
    OnBlurTextControl
} from '../shared/components.js';

class YoutubeSearchResult {

    constructor(response) {
        this.response = response;
        if (this.response) {
            this.data = this.response.data;
        }
    }

    isValid() {

        return this.response !== null;

    }

    getVideos() {

        if (!this.data || !this.data.length) {
            return [];
        }

        return this.data;

    }

    getPrevPage() {

        return this.response.prev_page;

    }

    getNextPage() {

        return this.response.next_page;

    }

}

export class YoutubeSearchController extends Component {

    constructor() {
        super( ...arguments );

        this.setAttributes = this.setAttributes.bind( this );

        this.state = {
            initialResponse: null,
            response: null,
            queryText: '',
            callStatus: 'idle'
        }

        this.queryData = {
            prevPage: null,
            nextPage: null
        }

    }

    setAttributes( attributes ) {

        this.props.setAttributes( attributes );
        this.resetQuery();
        this.setState({
            callStatus: 'idle'
        });

    }

    saveQuery(queryData, sender) {

        for (var key in queryData) {
            this.queryData[key] = queryData[key];
        }
        this.setState({
            callStatus: 'idle'
        });

    }

    resetQuery() {

        this.queryData = {
            prevPage: null,
            nextPage: null
        }

    }

    componentDidMount() {

        this.startRequest();

        this.setState({
            queryText: this.props.attributes.query
        });

    }

    componentDidUpdate( prevProps ) {

        if (this.state.callStatus === 'idle') {
            this.startRequest();
        }

    }

    startRequest() {

       this.call();

    }

    render() {

        const {
            attributes: {
                maxResults,
                order,
                publishedAfter,
                query,
                safeSearch,
                videoDefinition
            }
        } = this.props;

        if (!this.state.response || !this.state.initialResponse) {
            return __("Bezig met laden", "youtube-search");
        }

        if (!this.state.response.isValid() || !this.state.initialResponse.isValid()) {
            return __("Er is een fout opgetreden bij het laden van de showroom data", "youtube-search");
        }

        const inspectorControls = (
            <InspectorControls>
                <PanelBody
                    title={ __("Query", 'youtube-search') }
                    initialOpen={ true }
                    className='youtube-search-sidebar'
                >
                    <RangeControl
                        label={ __("Aantal items", 'youtube-search') }
                        value={ maxResults ? maxResults : 10 }
                        onChange={ (value) => {
                            this.setAttributes({
                                maxResults: value
                            });
                        } }
                    />
        			<OnBlurTextControl
                        label={ __("Zoektekst", "youtube-search") }
                        value={ this.state.queryText }
                        onChange={ (value) => {
                            this.setState({
                                queryText: value
                            });
                        } }
                        onBlur={ () => {
                            this.setAttributes({
                                query: this.state.queryText
                            });
                        } }
                    />
                    <SelectControl
                        label={ __("Sortering", 'youtube-search') }
                        value={ order ? order : 'relevance' }
                        onChange={ (value) => {
                            this.setAttributes({
                                order: value
                            });
                        } }
                        options={ [
                            { value: 'date', label: __("Datum", "youtube-search") },
                            { value: 'rating', label: __("Beoordeling", "youtube-search") },
                            { value: 'relevance', label: __("Relevantie", "youtube-search") },
                            { value: 'title', label: __("Titel", "youtube-search") },
                            { value: 'viewCount', label: __("Aantal views", "youtube-search") },
                        ] }
                    />
                    <BaseControl
                        label={ __("Gepubliceerd na", 'youtube-search') }
                    >
                        <DatePicker
                            currentDate={ publishedAfter !== undefined ? publishedAfter : null }
                            onChange={ (value) => {
                                this.setAttributes({
                                    publishedAfter: value
                                });
                            } }
                        />
                        {
                            publishedAfter &&
                            <a href="" onClick={ (event) => {
                                event.preventDefault();
                                this.setAttributes({
                                    publishedAfter: null
                                });
                            } }>Verwijder datum</a>
                        }
                    </BaseControl>
                    <SelectControl
                        label={ __("Safe search", 'youtube-search') }
                        value={ safeSearch ? safeSearch : 'moderate' }
                        onChange={ (value) => {
                            this.setAttributes({
                                safeSearch: value
                            });
                        } }
                        options={ [
                            { value: 'none', label: __("Geen safe search", "youtube-search") },
                            { value: 'moderate', label: __("Gematigd", "youtube-search") },
                            { value: 'strict', label: __("Strict", "youtube-search") }
                        ] }
                    />
                    <SelectControl
                        label={ __("Kwaliteit", 'youtube-search') }
                        value={ videoDefinition }
                        onChange={ (value) => {
                            this.setAttributes({
                                videoDefinition: value
                            });
                        } }
                        options={ [
                            { value: '', label: __("(geen kwaliteit gekozen)", "youtube-search") },
                            { value: 'any', label: __("Alle video's", "youtube-search") },
                            { value: 'high', label: __("Alleen HD", "youtube-search") },
                            { value: 'standard', label: __("Alleen SD", "youtube-search") }
                        ] }
                    />
                </PanelBody>
                {
                    this.getExtraControls(this.state.initialResponse)
                }
            </InspectorControls>
        );

        return ([
            this.renderForResponse(this.state.response),
            inspectorControls
        ]);

    }

    renderForResponse(response) {

        return '';

    }

    call(listPart) {

        const maxResults = this.props.attributes.maxResults ?
                           this.props.attributes.maxResults : 10;

        const query = this.props.attributes.query ?
                      this.props.attributes.query : '';

        const order = this.props.attributes.order ?
                      this.props.attributes.order : 'relevance';

        const publishedAfter = this.props.attributes.publishedAfter ?
                               this.props.attributes.publishedAfter.substr(0, 10) + 'T00:00:00Z' :
                               null;

        const safeSearch = this.props.attributes.safeSearch ?
                           this.props.attributes.safeSearch : 'moderate';

        var data = [
            {
                name: 'action',
                value: 'youtube_search'
            },
            {
                name: 'maxResults',
                value: maxResults
            },
            {
                name: 'q',
                value: query
            },
            {
                name: 'order',
                value: order
            },
            {
                name: 'safeSearch',
                value: safeSearch
            }
        ];

        if (publishedAfter) {
            data.push({
                name: 'publishedAfter',
                value: publishedAfter
            });
        }
        if (this.props.attributes.videoDefinition) {
            data.push({
                name: 'videoDefinition',
                value: this.props.attributes.videoDefinition
            });
        }

        if (listPart) {
            data.push({
                name: 'listPart',
                value: listPart
            });
        }

        jQuery.get(ajaxurl, data).done(
            (res) => {
                this.setState({
                    initialResponse: new YoutubeSearchResult(res.data)
                })
            }
        ).fail( (res) => {
            console.log('request failed');
        });

        var data = [
            {
                name: 'action',
                value: 'youtube_search'
            },
            {
                name: 'maxResults',
                value: maxResults
            },
            {
                name: 'q',
                value: query
            },
            {
                name: 'order',
                value: order
            },
            {
                name: 'safeSearch',
                value: safeSearch
            }
        ];

        if (publishedAfter) {
            data.push({
                name: 'publishedAfter',
                value: publishedAfter
            });
        }
        if (this.props.attributes.videoDefinition) {
            data.push({
                name: 'videoDefinition',
                value: this.props.attributes.videoDefinition
            });
        }

        if (listPart) {
            data.push({
                name: 'listPart',
                value: listPart
            });
        }

        if (this.queryData.prevPage) {
            data.push({
                name: 'pageToken',
                value: this.queryData.prevPage
            });
        }
        else if (this.queryData.nextPage) {
            data.push({
                name: 'pageToken',
                value: this.queryData.nextPage
            });
        }

        jQuery.get(ajaxurl, data).done(
            (res) => {
                this.setState({
                    response: new YoutubeSearchResult(res.data),
                    callStatus: 'pending'
                })
            }
        );

    }

    getExtraControls(initialResponse) {

        return '';

    }

}
