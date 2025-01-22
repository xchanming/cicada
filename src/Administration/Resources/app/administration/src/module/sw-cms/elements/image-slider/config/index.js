import template from './sw-cms-el-config-image-slider.html.twig';
import './sw-cms-el-config-image-slider.scss';

const { Mixin } = Cicada;
const {
    moveItem,
    object: { cloneDeep },
} = Cicada.Utils;
const Criteria = Cicada.Data.Criteria;

/**
 * @private
 * @package discovery
 */
export default {
    template,

    compatConfig: Cicada.compatConfig,

    inject: ['repositoryFactory'],

    emits: ['element-update'],

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    data() {
        return {
            mediaModalIsOpen: false,
            initialFolderId: null,
            entity: this.element,
            mediaItems: [],
            showSlideConfig: false,
        };
    },

    computed: {
        uploadTag() {
            return `cms-element-media-config-${this.element.id}`;
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        defaultFolderName() {
            return this.cmsPageState.pageEntityName;
        },

        items() {
            if (this.element.config && this.element.config.sliderItems && this.element.config.sliderItems.value) {
                return this.element.config.sliderItems.value;
            }

            return [];
        },

        speedDefault() {
            return this.cmsService.getCmsElementConfigByName('image-slider').defaultConfig.speed.value;
        },

        autoplayTimeoutDefault() {
            return this.cmsService.getCmsElementConfigByName('image-slider').defaultConfig.autoplayTimeout.value;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            this.initElementConfig('image-slider');

            if (this.element.config.autoSlide?.value) {
                this.showSlideConfig = true;
            }

            if (this.element.config.sliderItems.source !== 'default' && this.element.config.sliderItems.value.length > 0) {
                const mediaIds = this.element.config.sliderItems.value.map((configElement) => {
                    return configElement.mediaId;
                });

                const criteria = new Criteria(1, 25);
                criteria.setIds(mediaIds);

                const searchResult = await this.mediaRepository.search(criteria);

                this.mediaItems = mediaIds
                    .map((mediaId) => {
                        return searchResult.get(mediaId);
                    })
                    .filter((mediaItem) => mediaItem !== null);

                this.element.config.sliderItems.value.forEach((item, i) => {
                    if (searchResult.get(item.mediaId) === null) {
                        this.onItemRemove({ id: item.mediaId }, i);
                    }
                });
            }
        },

        onImageUpload(mediaItem) {
            const sliderItems = this.element.config.sliderItems;
            if (sliderItems.source === 'default') {
                sliderItems.value = [];
                sliderItems.source = 'static';
            }

            // Check if mediaItem already exists in mediaItems
            const mediaItemExists = this.mediaItems.find((item) => {
                return item.id === mediaItem.id;
            });

            // Remove previous mediaItem if it already exists
            if (mediaItemExists) {
                this.mediaItems = this.mediaItems.filter((item) => {
                    return item.id !== mediaItem.id;
                });

                sliderItems.value = sliderItems.value.filter((item) => {
                    return item.mediaId !== mediaItem.id;
                });
            }

            sliderItems.value.push({
                mediaUrl: mediaItem.url,
                mediaId: mediaItem.id,
                url: null,
                newTab: false,
            });

            this.mediaItems.push(mediaItem);

            this.updateMediaDataValue();
            this.emitUpdateEl();
        },

        onItemRemove(mediaItem, index) {
            const key = mediaItem.id;
            const { value } = this.element.config.sliderItems;

            this.element.config.sliderItems.value = value.filter((item, i) => {
                return item.mediaId !== key || i !== index;
            });

            this.mediaItems = this.mediaItems.filter((item, i) => {
                return item.id !== key || i !== index;
            });

            this.updateMediaDataValue();
            this.emitUpdateEl();
        },

        onCloseMediaModal() {
            this.mediaModalIsOpen = false;
        },

        onMediaSelectionChange(mediaItems) {
            const sliderItems = this.element.config.sliderItems;
            if (sliderItems.source === 'default') {
                sliderItems.value = [];
                sliderItems.source = 'static';
            }

            mediaItems.forEach((item) => {
                this.element.config.sliderItems.value.push({
                    mediaUrl: item.url,
                    mediaId: item.id,
                    url: null,
                    newTab: false,
                });
            });

            this.mediaItems.push(...mediaItems);

            this.updateMediaDataValue();
            this.emitUpdateEl();
        },

        onItemSort(dragData, dropData) {
            moveItem(this.mediaItems, dragData.position, dropData.position);
            moveItem(this.element.config.sliderItems.value, dragData.position, dropData.position);

            this.updateMediaDataValue();
            this.emitUpdateEl();
        },

        updateMediaDataValue() {
            if (this.element.config.sliderItems.value) {
                const sliderItems = cloneDeep(this.element.config.sliderItems.value);

                sliderItems.forEach((sliderItem) => {
                    this.mediaItems.forEach((mediaItem) => {
                        if (sliderItem.mediaId === mediaItem.id) {
                            sliderItem.media = mediaItem;
                        }
                    });
                });

                if (!this.element.data) {
                    if (this.isCompatEnabled('INSTANCE_SET')) {
                        this.$set(this.element, 'data', { sliderItems });
                    } else {
                        this.element.data = { sliderItems };
                    }

                    return;
                }

                if (this.isCompatEnabled('INSTANCE_SET')) {
                    this.$set(this.element.data, 'sliderItems', sliderItems);
                } else {
                    this.element.data.sliderItems = sliderItems;
                }
            }
        },

        onOpenMediaModal() {
            this.mediaModalIsOpen = true;
        },

        onChangeMinHeight(value) {
            this.element.config.minHeight.value = value === null ? '' : value;

            this.$emit('element-update', this.element);
        },

        onChangeAutoSlide(value) {
            this.showSlideConfig = value;

            if (!value) {
                this.element.config.autoplayTimeout.value = this.autoplayTimeoutDefault;
                this.element.config.speed.value = this.speedDefault;
            }
        },

        onChangeDisplayMode(value) {
            if (value === 'cover') {
                this.element.config.verticalAlign.value = null;
            }

            this.$emit('element-update', this.element);
        },

        emitUpdateEl() {
            this.$emit('element-update', this.element);
        },

        onChangeIsDecorative(value) {
            this.element.config.isDecorative.value = value;

            this.$emit('element-update', this.element);
        },
    },
};
