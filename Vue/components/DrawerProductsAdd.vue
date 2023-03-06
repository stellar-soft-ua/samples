<script>
import {mapMutations, mapState, mapActions} from 'vuex';

export default {
    props: {
        initialSelectedProduct: {
            type: [Boolean, Object],
            default: false
        }
    },
    data() {
        return {
            selectedVariant: null,
            selectVariantMode: false,
            activeProduct: false,
            addingVariant: false
        };
    },
    computed: {
        ...mapState('subscriptions', ['selectedSubscription']),

        ...mapState('products', ['products']),

        ...mapState('collections', [
            'collections',
            'activeCollection',
            'collectionProducts',
            'availableCollections',
            'collectionsLoaded'
        ]),

        ...mapState('store', ['storeSettings']),

        selectedSubscriptionProducts() {
            return this.selectedSubscription.items;
        }
    },
    mounted() {
        const {initialSelectedProduct} = this;
        if (initialSelectedProduct) {
            this.activeProduct = {...initialSelectedProduct};
            this.selectVariantMode = true;
        }
    },
    methods: {
        ...mapMutations('variantSelectProduct', ['setVariantSelectProduct']),

        ...mapMutations('subscriptions', ['setSelectedSubscription']),

        ...mapActions('subscriptions', ['SUBSCRIPTION_ADD_ITEMS', 'SUBSCRIPTION_GET']),

        ...mapActions('products', ['GET_PRODUCTS']),

        ...mapActions('collections', ['LIST_COLLECTIONS', 'GET_COLLECTION_BY_ID']),

        ...mapMutations('collections', ['setActiveCollection']),

        async addVariant() {
            const {selectedVariant, selectedSubscription} = this;

            this.addingVariant = true;

            try {
                await this.SUBSCRIPTION_ADD_ITEMS({
                    id: selectedSubscription.id,
                    variantId: selectedVariant.id
                });

                const updatedSubscription = await this.SUBSCRIPTION_GET(selectedSubscription.id);
                this.setSelectedSubscription(updatedSubscription);

                this.$toasted.global.success({
                    message: this.atc('notices.productAddedToSubscription')
                });
                this.$emit('close');
            } catch (e) {
                this.$toasted.global.error({
                    message: e.message
                });
            } finally {
                this.selectVariantMode = false;
                this.addingVariant = false;
                this.$emit('setMode', 'edit');
            }
        },

        changeCurrentFilter(collection) {
            const {activeCollection} = this;
            if (!collection) {
                // reset to all
                this.setActiveCollection(false);
                this.loadProductsByCollectionId(false);
            } else {
                // same filter ignore
                if (activeCollection && collection.id === activeCollection.id) return;
                this.setActiveCollection(collection);
                this.loadProductsByCollectionId(collection);
            }
        },

        async loadProductsByCollectionId(collection) {
            try {
                await this.GET_PRODUCTS({
                    collectionId: collection ? collection.id : undefined
                });
            } catch (e) {
                console.error(e);
            }
        },

        handleVariantSelectProduct(product) {
            this.activeProduct = {...product};
            this.selectVariantMode = true;
        }
    }
};
</script>

<template>
    <div>
        <!-- <h2 class="c-drawer__title">
          {{ activeProduct ? activeProduct.title : 'Select Product' }}
        </h2> -->

        <h3 v-if="activeProduct" class="mt-3">
            {{ atc('labels.selectVariant') }}
        </h3>

        <v-container v-if="selectedSubscription.delivery" class="py-3 text-center">
      <span class="font-weight-medium text-body-1 text-center">{{
              atc(
                  'general.notices.productsShipEvery',
                  `${selectedSubscription.delivery.interval} ${intervalUnitDisplay(
                      selectedSubscription.delivery.frequency.toLowerCase(),
                      selectedSubscription.delivery.interval
                  )}`
              )
          }}</span>
        </v-container>

        <div v-if="products && products.length && collectionsLoaded && !selectVariantMode" class="c-productsGrid__top">
            <ul v-if="!availableCollections || !availableCollections.length" class="c-productsGrid__link-contain">
                <li class="c-productsGrid__link c-productsGrid__link--active">
                    <button>{{ atc('labels.all') }}</button>
                </li>
            </ul>

            <ul v-else class="c-productsGrid__link-contain">
                <li
                    v-if="!storeSettings.cp_disable_product_all_tab"
                    class="c-productsGrid__link"
                    :class="{
            'c-productsGrid__link--active': isEmptyObject(activeCollection)
          }"
                >
                    <button @click="changeCurrentFilter(false)">
                        {{ atc('labels.all') }}
                    </button>
                </li>

                <li
                    v-for="collection in availableCollections"
                    :key="collection.id"
                    class="c-productsGrid__link"
                    :class="{
            'c-productsGrid__link--active': activeCollection && activeCollection.id === collection.id
          }"
                >
                    <button @click="changeCurrentFilter(collection)">
                        {{ collection.title }}
                    </button>
                </li>
            </ul>
        </div>

        <div v-if="products && products.length">
            <v-card-text v-if="!selectVariantMode">
                <div class="c-drawerDeliveryFrequency__options">
                    <drawer-product-block
                        v-for="product in products"
                        :key="product.id"
                        :product="product"
                        add
                        @variantSelectProduct="handleVariantSelectProduct"
                    />
                </div>
            </v-card-text>

            <v-card-text v-if="activeProduct.variants && selectVariantMode" align="center">
                <v-checkbox
                    v-for="variant in activeProduct.variants"
                    :key="variant.shopify_id"
                    v-model="selectedVariant"
                    :label="`${variant.title} - $${variant.price}`"
                    :value="variant"
                >{{
                    }}
                </v-checkbox>
            </v-card-text>

            <vue-pagination
                v-if="!selectVariantMode"
                key="vue-pagination-add-drawer"
                ref="vue-pagination"
                :collection-id="activeCollection ? activeCollection.id : false"
            />

            <div class="c-drawer__actionButtons">
                <v-btn
                    v-if="selectVariantMode && !selectVariantMode"
                    class="c-form__submitButton"
                    tile
                    color="primary"
                    @click="$emit('setMode', 'edit')"
                >{{ atc('actions.addProduct') }}
                </v-btn
                >
                <v-btn
                    class="c-form__submitButton"
                    tile
                    color="error"
                    :disabled="addingVariant"
                    @click="$emit('setMode', 'edit')"
                >{{ atc('actions.cancel') }}
                </v-btn
                >
                <v-btn
                    v-if="selectVariantMode && selectVariantMode"
                    class="c-form__submitButton"
                    tile
                    color="primary"
                    :loading="addingVariant"
                    @click="addVariant"
                >{{ atc('actions.addVariant') }}
                </v-btn
                >
            </div>
        </div>

        <v-container v-else class="text-center pt-6">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
        </v-container>
    </div>
</template>
