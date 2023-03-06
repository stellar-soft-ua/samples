<script>
import { mapMutations, mapState, mapActions } from 'vuex';

export default {
  components: {
    // VuePagination,
  },
  data: () => {
    return {
      selectVariantMode: false,
      selectedVariant: false,
      productToSwapTo: false,
      swapping: false,
      swappableProductsLoaded: false
    };
  },
  computed: {
    ...mapState('subscriptions', ['selectedSubscription']),

    ...mapState('swapProduct', ['swapProduct']),

    ...mapState('products', ['products']),

    ...mapState('collections', [
      'collections',
      'activeCollection',
      'collectionProducts',
      'availableCollections',
      'collectionsLoaded'
    ]),

    ...mapState('store', ['storeSettings'])
  },

  async mounted() {
    const { selectedSubscription, swapProduct } = this;

    this.clearProducts();

    try {
      await this.LIST_SUBSCRIPTION_ITEM_PRODUCTS({
        subId: selectedSubscription.id,
        itemId: swapProduct.id
      });
    } catch (e) {
      console.error('LIST_SUBSCRIPTION_ITEM_PRODUCTS: ', e);
    } finally {
      this.swappableProductsLoaded = true;
    }
  },

  methods: {
    ...mapMutations('swapProduct', ['setSwapProduct']),

    ...mapMutations('variantSelectProduct', ['setVariantSelectProduct']),

    ...mapMutations('subscriptions', ['setSelectedSubscription']),

    ...mapActions('subscriptions', ['SUBSCRIPTION_ADD_ITEMS', 'SUBSCRIPTION_DELETE_ITEM', 'SUBSCRIPTION_GET']),

    ...mapActions('products', ['GET_PRODUCTS', 'LIST_SUBSCRIPTION_ITEM_PRODUCTS']),

    ...mapMutations('products', ['clearProducts']),

    ...mapActions('collections', ['LIST_COLLECTIONS', 'GET_COLLECTION_BY_ID']),

    ...mapMutations('collections', ['setActiveCollection']),

    handleVariantSelectProduct(product) {
      this.productToSwapTo = { ...product };
      this.selectVariantMode = true;

      // this.setVariantSelectProduct(product);
      // this.$emit('setMode', 'variant-select');
    },

    async handleSwapProduct() {
      const { selectedVariant, selectedSubscription, swapProduct } = this;
      this.swapping = true;

      try {
        await this.SUBSCRIPTION_ADD_ITEMS({
          id: selectedSubscription.id,
          variantId: parseInt(selectedVariant.id)
        });
        await this.SUBSCRIPTION_DELETE_ITEM({
          id: selectedSubscription.id,
          itemId: swapProduct.id
        });

        const updatedSubscription = await this.SUBSCRIPTION_GET(selectedSubscription.id);
        this.setSelectedSubscription(updatedSubscription);

        this.$toasted.global.success({
          message: this.atc('notices.productSwappedOnSubscription')
        });
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.$emit('setMode', 'edit');
        this.selectVariantMode = false;
        this.selectedVariant = false;
        this.productToSwapTo = false;
        this.swapping = false;
      }

      this.$emit('close');
    },

    changeCurrentFilter(collection) {
      const { activeCollection } = this;
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

    async handleSwapProductState(newProduct) {
      this.setVariantSelectProduct(newProduct);
      this.$emit('setMode', 'variant-select-swap');
    }
  }
};
</script>

<template>
  <div>
    <h2 class="c-drawer__title">{{ atc('labels.swapProduct') }}</h2>

    <p class="c-drawer__subtitle">{{ atc('labels.currentProduct') }}</p>

    <div class="c-drawerDeliveryFrequency__options">
      <drawer-product-block :product="swapProduct" existing-product />
    </div>

    <p v-if="(!products || !products.length) && swappableProductsLoaded" class="c-drawer__subtitle">
      {{ atc('actions.selectProduct') }}
    </p>

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

      <v-radio-group v-if="productToSwapTo.variants && selectVariantMode" v-model="selectedVariant">
        <v-radio
          v-for="variant in productToSwapTo.variants"
          :key="variant.shopify_id"
          :value="variant"
          @click="selectedVariant = variant"
        >
          <template v-slot:label>
            <div class="d-flex justify-center c-productsSelector">
              <img :src="variant.image" />
              <div class="ml-4">
                <h4>
                  {{ productToSwapTo.title }}
                  <span v-if="variant.title !== 'Default Title'">{{ variant.title }}</span>
                </h4>
                <h5>$ {{ formatMoney(parseFloat(variant.price).toFixed(2)) }}</h5>
              </div>
            </div>
          </template>
        </v-radio>
      </v-radio-group>

      <vue-pagination
        v-if="!selectVariantMode"
        key="vue-pagination-add-drawer"
        ref="vue-pagination"
        :collection-id="false"
        :item-id="swapProduct.id"
        :subscription-id="selectedSubscription.id"
      />

      <div class="c-drawer__actionButtons">
        <v-btn class="c-form__submitButton" color="error" tile @click="$emit('setMode', 'edit')">{{
          atc('actions.cancel')
        }}</v-btn>

        <v-btn
          v-if="selectVariantMode && selectVariantMode"
          class="c-form__submitButton"
          color="primary"
          tile
          :loading="swapping"
          @click="handleSwapProduct"
          >{{ atc('actions.swapProduct') }}</v-btn
        >
      </div>
    </div>

    <div v-else-if="(!products || !products.length) && swappableProductsLoaded">
      <v-card-text>
        <p>No products available for swap.</p>
      </v-card-text>

      <div class="c-drawer__actionButtons">
        <v-btn class="c-form__submitButton" color="error" tile @click="$emit('setMode', 'edit')">Cancel</v-btn>
      </div>
    </div>

    <v-container v-else class="text-center pt-6">
      <v-progress-circular indeterminate color="primary"></v-progress-circular>
    </v-container>
  </div>
</template>

<style lang="scss">
.c-productsSelector {
  img {
    width: 70px;
    height: auto;
  }
}
</style>
