<script>
import { mapMutations, mapState, mapActions } from 'vuex';

export default {
  props: {
    updating: {
      type: Boolean,
      default: false
    }
  },
  data: () => {
    return {
      updatingItemId: false
    };
  },
  computed: {
    ...mapState('subscriptions', ['selectedSubscription']),

    ...mapState('products', ['products'])
  },
  async mounted() {
    if (!this.products || !this.products.length) {
      await this.loadProducts();
    }
  },
  methods: {
    ...mapMutations('swapProduct', ['setSwapProduct']),

    ...mapActions('subscriptions', [
      'SUBSCRIPTION_UPDATE',
      'SUBSCRIPTION_ADD_ITEMS',
      'SUBSCRIPTION_UPDATE_ITEM',
      'SUBSCRIPTION_DELETE_ITEM',
      'SUBSCRIPTION_GET'
    ]),

    ...mapMutations('subscriptions', ['setSelectedSubscription']),

    ...mapActions('products', ['LIST_PRODUCTS', 'GET_PRODUCT_VARIANTS']),

    isOutOfStockVariant({ inventory_management, inventory_policy, inventory_quantity }) {
      if ((inventory_management === 'shopify' && inventory_policy === 'continue') || inventory_quantity > 0)
        return false;

      return true;
    },

    async selectProduct(product) {
      this.selectedProduct = { ...product };

      try {
        const variants = await this.GET_PRODUCT_VARIANTS(product.shopify_id.split('Product/')[1]);
        this.selectedProductVariants = [...variants];
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      }

      this.selectVariantModalOpen = true;
    },

    async loadProducts() {
      try {
        await this.LIST_PRODUCTS();
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.tableLoaded = true;
      }
    },

    handleSwapProduct(product) {
      if (this.updating) return;
      this.setSwapProduct(product);
      this.$emit('setMode', 'swap');
    },

    async updateQuantity({ item, increase }) {
      const { selectedSubscription } = this;

      this.updatingItemId = item.id;
      try {
        const subscriptionItems = await this.SUBSCRIPTION_UPDATE_ITEM({
          id: selectedSubscription.id,
          itemId: item.id,
          quantity: increase ? (item.quantity += 1) : (item.quantity -= 1)
        });

        this.subscriptionItems = subscriptionItems;
        // reseting the price
        const updatedSubscription = await this.SUBSCRIPTION_GET(selectedSubscription.id);
        this.setSelectedSubscription(updatedSubscription);
        this.$toasted.global.success({
          message: this.atc('notices.itemUpdated')
        });
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.updatingItemId = false;
      }
    },

    async removeItem(item) {
      const { selectedSubscription } = this;

      this.removingItemId = item.id;

      try {
        await this.SUBSCRIPTION_DELETE_ITEM({
          id: selectedSubscription.id,
          itemId: item.id
        });

        const updatedSubscription = await this.SUBSCRIPTION_GET(selectedSubscription.id);
        this.setSelectedSubscription(updatedSubscription);
        this.$toasted.global.success({
          message: this.atc('general.notices.removedItemFromSubscription', item.title)
        });
      } catch (e) {
        console.error('updateSubscription: ', e.message);
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.removingItemId = false;
      }
    },

    async addItem({ item, increase }) {
      const { selectedSubscription } = this;

      this.updatingItemId = item.id;
      try {
        const subscriptionItems = await this.SUBSCRIPTION_UPDATE_ITEM({
          id: selectedSubscription.id,
          itemId: item.id,
          quantity: increase ? (item.quantity += 1) : (item.quantity -= 1)
        });

        this.subscriptionItems = subscriptionItems;
        this.$toasted.global.success({
          message: this.atc('notices.itemUpdated')
        });
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.updatingItemId = false;
      }
    },

    async addVariant() {
      const { selectedVariantId, contract, selectedSubscription } = this;

      try {
        await this.SUBSCRIPTION_ADD_ITEMS({
          id: contract.id,
          variantId: selectedVariantId
        });

        const updatedSubscription = await this.SUBSCRIPTION_GET(selectedSubscription.id);
        this.setSelectedSubscription(updatedSubscription);
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      }

      this.selectVariantModalOpen = false;
      this.selectedVariantId = null;

      this.$emit('updatedProducts');
    }
  }
};
</script>

<template>
  <div v-if="selectedSubscription">
    <v-container v-if="selectedSubscription.delivery" class="py-3 text-center">
      <span class="font-weight-medium text-body-1 text-center">{{
        atc(
          'labels.productsShipEvery',
          `${selectedSubscription.delivery.interval} ${intervalUnitDisplay(
            selectedSubscription.delivery.frequency.toLowerCase(),
            selectedSubscription.delivery.interval
          )}`
        )
      }}</span>
    </v-container>

    <div class="c-drawerDeliveryFrequency__options">
      <drawer-product-block
        v-for="(item, index) in selectedSubscription.items"
        :key="item.id + '-' + index"
        :product="item"
        :remove="selectedSubscription.items.length > 1"
        swap
        quantity
        existing-product
        :updating="updatingItemId === item.id"
        @swapProduct="handleSwapProduct"
        @removeProduct="removeItem"
        @updateQuantity="updateQuantity"
      />
    </div>
  </div>
</template>
