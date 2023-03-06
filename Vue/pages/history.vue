<script>
import { mapState, mapActions } from 'vuex';
import { EventBus } from '~/utils/event-bus';
// import moment from 'moment';

export default {
  data: () => {
    return {
      ordersLoaded: false
    };
  },
  computed: {
    ...mapState('customers', ['orders', 'customer']),

    ...mapState('store', ['store'])
  },
  async mounted() {
    const { customer } = this;
    try {
      let customerLocal = customer || false;
      if (!customer || !customer.id) {
        customerLocal = await this.CUSTOMER_GET_ME();
      }

      await this.CUSTOMER_GET_ORDERS(customerLocal.id);
    } catch (e) {
      console.error(e);
      this.$toasted.global.error({
        message: e.message
      });
    } finally {
      this.ordersLoaded = true;
    }
  },
  destroyed() {
    EventBus.$emit('abortRequest');
  },
  methods: {
    ...mapActions('customers', ['CUSTOMER_GET_ORDERS', 'CUSTOMER_GET_ME'])
  },
};
</script>

<template>
  <section class="c-history">
    <v-container v-if="!ordersLoaded">
      <div>
        <h1 class="text-h4 mb-2">
          {{ atc('labels.pastOrders') }}
        </h1>

        <v-progress-circular class="d-block mx-auto my-10" indeterminate color="primary"></v-progress-circular>
      </div>
    </v-container>

    <v-container v-else-if="ordersLoaded && (!orders || !orders.length)" class="c-history">
      <div>
        <h1 class="text-h4 mb-2">
          {{ atc('labels.pastOrders') }}
        </h1>

        <h2 class="c-noSubscriptions__text text-center">
          {{ atc('notices.general.notices.noSubscriptionsHistory') }}
        </h2>
      </div>
    </v-container>

    <v-container v-else>
      <h1 class="text-h4 mb-2">
        {{ atc('labels.pastOrders') }}
      </h1>

      <v-row justify="center" class="mt-10">
        <v-expansion-panels accordion>
          <history-order v-for="(order, i) in orders" :key="i" :order="order" />
        </v-expansion-panels>
      </v-row>
    </v-container>
  </section>
</template>

<script>
import { mapState, mapActions } from 'vuex';
// import moment from 'moment';

export default {
  data: () => {
    return {
      ordersLoaded: false
    };
  },
  computed: {
    ...mapState('customers', ['orders', 'customer']),

    ...mapState('store', ['store'])
  },
  async mounted() {
    const { customer } = this;
    try {
      let customerLocal = customer || false;
      if (!customer || !customer.id) {
        customerLocal = await this.CUSTOMER_GET_ME();
      }
      await this.CUSTOMER_GET_ORDERS(customerLocal.id);
    } catch (e) {
      console.error(e);
      this.$toasted.global.error({
        message: e.message
      });
    } finally {
      this.ordersLoaded = true;
    }
  },
  methods: {
    ...mapActions('customers', ['CUSTOMER_GET_ORDERS', 'CUSTOMER_GET_ME'])
  },
  head() {
    return { title: this.store.shop_name || 'History' };
  }
};
</script>
