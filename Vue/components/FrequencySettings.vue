<script>
import { mapState, mapActions } from 'vuex';

export default {
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },

  data: () => {
    return {
      selectedInterval: null,
      shippingIntervalOptions: false
    };
  },

  computed: {
    ...mapState('subscriptions', ['selectedSubscription']),
    sellingPlanId() {
      return this.selectedSubscription?.items[0]?.selling_plan_id;
    }
  },

  mounted() {
    this.fetchPlanInformation();
  },

  methods: {
    ...mapActions('subscriptions', ['SUBSCRIPTION_UPDATE', 'SUBSCRIPTION_PLAN_GET']),
    ...mapActions('subscriptions', ['SUBSCRIPTIONS_LIST']),

    async fetchPlanInformation() {
      try {
        const plan = await this.SUBSCRIPTION_PLAN_GET(this.sellingPlanId);
        this.shippingIntervalOptions = plan.options.map((item) => item.delivery);
        this.selectedInterval = this.selectedSubscription.delivery;
      } catch (e) {
        console.error(e);
        this.$toasted.global.error({
          message: e.message
        });
      }
    },

    async updateFrequency() {
      const { selectedSubscription } = this;
      this.showLoader(true);
      try {
        await this.SUBSCRIPTION_UPDATE({
          id: selectedSubscription.id,
          payload: {
            delivery: this.selectedInterval,
            billing: {
              ...this.selectedSubscription.billing,
              ...this.selectedInterval
            }
          }
        });
        // await this.SUBSCRIPTIONS_LIST(window.localStorage.customerId);
      } catch (e) {
        console.error('updateFrequency e: ', e);
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.showLoader(false);
        this.$emit('close');
        this.$toasted.global.success({
          message: this.atc('notices.updatedShippingFrequency')
        });
      }
    }
  }
};
</script>

<template>
  <div>
    <v-radio-group v-if="shippingIntervalOptions" v-model="selectedInterval" @change="updateFrequency()">
      <v-radio
        v-for="(option, index) in shippingIntervalOptions"
        :key="index"
        class="c-upscribeSelectOption"
        :label="`${option.interval} ${option.frequency}` | plural"
        :value="option"
      ></v-radio>
    </v-radio-group>
    <div v-else class="d-flex justify-center align-center mt-10">
      <v-progress-circular :size="50" :width="7" indeterminate color="primary"></v-progress-circular>
    </div>
  </div>
</template>

<style lang="scss">
.c-upscribeSelectOption {
  display: flex;
  padding: 20px;
  border: 1px solid rgba(0, 0, 0, 0.2);

  .v-label {
    font-size: 22px;
    color: #212121;
  }
}
</style>
