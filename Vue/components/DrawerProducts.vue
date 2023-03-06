<script>
import { mapState } from 'vuex';

export default {
  props: {
    show: {
      type: Boolean,
      default: false
    },
    initialMode: {
      type: [Boolean, String],
      default: false
    },
    initialSelectedProduct: {
      type: [Boolean, Object],
      default: false
    }
  },
  data: () => {
    return {
      mode: 'edit' // edit, add, swap
    };
  },
  computed: {
    ...mapState('subscriptions', ['selectedSubscription']),

    drawerTitle() {
      const { mode } = this;
      if (mode === 'swap') {
        return this.atc('labels.swapProducts');
      } else if (mode === 'add') {
        return this.atc('labels.addProducts');
      } else if (mode === 'variant-select') {
        return this.atc('labels.selectVariant');
      } else if (mode === 'variant-select-swap') {
        return this.atc('labels.selectVariant');
      } else {
        return this.atc('labels.editProducts');
      }
    }
  },
  mounted() {
    const { initialMode } = this;
    if (initialMode) {
      this.mode = initialMode;
    }
  },
  methods: {
    handleSetMode(mode) {
      this.mode = mode;
    }
  }
};
</script>

<template>
  <drawer v-if="selectedSubscription" :show="show" :title="drawerTitle" class="text-center" @close="$emit('close')">
    <drawer-products-swap
      v-if="mode === 'swap'"
      class="c-drawerProducts c-drawer"
      @setMode="handleSetMode"
      @close="$emit('close')"
    />

    <drawer-products-add
      v-else-if="mode === 'add'"
      class="c-drawerProducts c-drawer"
      :initial-selected-product="initialSelectedProduct"
      @setMode="handleSetMode"
      @close="$emit('close')"
    />

    <drawer-products-select-variant
      v-else-if="mode === 'variant-select'"
      class="c-drawerProducts c-drawer"
      @setMode="handleSetMode"
      @close="$emit('close')"
    />

    <drawer-products-select-variant-swap
      v-else-if="mode === 'variant-select-swap'"
      class="c-drawerProducts c-drawer"
      @setMode="handleSetMode"
      @close="$emit('close')"
    />

    <drawer-products-edit v-else class="c-drawerProducts c-drawer" @setMode="handleSetMode" @close="$emit('close')" />
  </drawer>
</template>
