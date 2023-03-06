<script>
import Vue from 'vue';
import { mapActions, mapState, mapMutations } from 'vuex';
import moment from 'moment-timezone';

export default Vue.extend({
  data() {
    return {
      moment,
      loaded: false,
      customer: false,
      selectedTab: 0
    };
  },
  computed: {
    ...mapState('api', ['storeDomain']),

    ...mapState('store', ['store']),

    ...mapState('subscriptions', ['subscriptions']),

    activeSubscriptions() {
      return this.subscriptions.filter((item) => item.status === 'active');
    },

    inactiveSubscriptions() {
      return this.subscriptions.filter((item) => item.status !== 'active');
    }
  },
  // async mounted() {
  //   let customerId = false;

  //   try {
  //     const customer = await this.CUSTOMER_GET_ME();

  //     customerId = customer.id;

  //     this.setCustomer(customer);

  //     this.customer = { ...customer };
  //   } catch (e) {
  //     this.$toasted.global.error({
  //       message: e.message
  //     });
  //   }

  //   // We're on an individual contract route
  //   if (customerId) {
  //     try {
  //       await Promise.all([
  //         this.SUBSCRIPTIONS_LIST(customerId)
  //         // this.CUSTOMER_GET_CHARGES(customerId),
  //         // this.CUSTOMER_GET_REFUNDS(customerId),
  //       ]);
  //     } catch (e) {
  //       this.$toasted.global.error({
  //         message: e.message
  //       });
  //     } finally {
  //       this.loaded = true;
  //     }
  //   }
  // },
  methods: {
    ...mapActions('customers', ['CUSTOMER_GET', 'CUSTOMER_GET_CHARGES', 'CUSTOMER_GET_REFUNDS', 'CUSTOMER_GET_ME']),

    ...mapMutations('customers', ['setCustomer']),

    ...mapActions('subscriptions', ['SUBSCRIPTIONS_LIST'])
  },
  head() {
    return { title: this.store.shop_name || 'Subscriptions' };
  }
});
</script>

<template>
  <section class="c-home">
    <v-container v-if="subscriptions && subscriptions.length">
      <h1 v-if="subscriptions" class="text-h4 mb-2">
        {{ atc('labels.subscriptions') }}
      </h1>
      <h1 v-else class="text-h4 mb-2">
        {{ atc('notices.noSubscriptionsAvailable') }}
      </h1>

      <v-tabs v-model="selectedTab" background-color="transparent" class="mb-10" active-class="font-weight-bold">
        <v-tab key="0" class="font-weight-normal text-capitalize subtitle-1">
          {{ atc('labels.activeSubscriptions') }} ({{ activeSubscriptions.length }})
        </v-tab>
        <v-tab key="1" class="font-weight-normal text-capitalize subtitle-1">
          {{ atc('labels.inactiveSubscriptions') }} ({{ inactiveSubscriptions.length }})
        </v-tab>
      </v-tabs>

      <v-tabs-items v-model="selectedTab">
        <v-tab-item key="0" class="blue-grey py-2 lighten-5">
          <subscription-list-item
            v-for="subscription in activeSubscriptions"
            :key="subscription.id"
            :subscription="subscription"
            class="mb-10"
          />
        </v-tab-item>
        <v-tab-item key="1" class="blue-grey py-2 lighten-5">
          <subscription-list-item
            v-for="subscription in inactiveSubscriptions"
            :key="subscription.id"
            :subscription="subscription"
            class="mb-4"
          />
        </v-tab-item>
      </v-tabs-items>
    </v-container>

    <v-container v-else-if="!subscriptions || !subscriptions.length">
      <h1 class="title-2 mb-2">
        {{ atc('notices.noSubscriptionsAvailable') }}
      </h1>
    </v-container>

    <v-container v-else class="text-center">
      <v-progress-circular indeterminate color="primary"></v-progress-circular>
    </v-container>
  </section>
</template>

<style lang="scss">
.c-tab--active {
  font-weight: bold;
}
</style>
