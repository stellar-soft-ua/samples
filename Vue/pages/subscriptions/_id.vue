<script>
import { mapState, mapMutations, mapActions, mapGetters } from 'vuex';
import { EventBus } from '~/utils/event-bus';

export default {
  data: () => {
    return {
      subscriptionsLoaded: false,

      editNameModalOpen: false,
      nameLocal: '',
      updatingName: false,
      mode: 'subscription',
      nextChargeLoading: false,
      nextCharge: {}
    };
  },

  computed: {
    ...mapState('subscriptions', ['subscriptions', 'selectedSubscription', 'noselectedSubscriptions']),

    ...mapGetters('subscriptions', ['getSubscriptionById']),

    ...mapState('store', ['store']),

    subscriptionActive() {
      if (!this.selectedSubscription) return false;
      return this.selectedSubscription.status === 'active';
    }
  },

  watch: {
    async mode(val) {
      if (val === 1) {
        this.$router.push({ query: { nextCharge: true } });
        this.$toasted.global.info({
          message: `You can only change the next order's address`
        });
      } else {
        this.$router.push({ query: { nextCharge: '' } });
      }
    }
  },

  async mounted() {
    let { subscriptions, customer, $route } = this;

    try {
      if (!subscriptions || !subscriptions.length) {
        subscriptions = await this.SUBSCRIPTIONS_LIST(customer.id);
      }

      let subscriptionId;
      if ($route.params.id) {
        subscriptionId = this.$route.params.id;
      } else if (subscriptions?.length) {
        subscriptionId = subscriptions[0].id;
      } else {
        this.$router.push({
          name: 'index'
        });
      }

      const subscription = this.getSubscriptionById(+subscriptionId);
      this.setSelectedSubscription(subscription);

      this.subscriptionsLoaded = true;
    } catch (e) {
      this.$toasted.global.error({
        message: e.message
      });
    }
  },
  destroyed() {
    EventBus.$emit('abortRequest');
  },
  methods: {
    ...mapActions('customers', ['CUSTOMER_GET_ME']),

    ...mapMutations('subscriptions', ['setSelectedSubscription', 'setSelectedSubscriptionById']),

    ...mapActions('subscriptions', [
      'SUBSCRIPTIONS_LIST',
      'SUBSCRIPTION_GET',
      'SUBSCRIPTION_LIST_ITEMS',
      'SUBSCRIPTION_GET_ITEM',
      'SUBSCRIPTION_ADD_ITEMS',
      'SUBSCRIPTION_UPDATE_ITEM',
      'SUBSCRIPTION_DELETE_ITEM',
      'SUBSCRIPTION_ACTION',
      'SUBSCRIPTION_SEND_UPDATE_PAYMENT_METHOD_EMAIL',
      'SUBSCRIPTION_CHARGE_NOW',
      'SUBSCRIPTION_RENAME',
      'GET_CHARGE_FORCASTS'
    ]),

    editSubscriptionName() {
      const { selectedSubscription } = this;
      this.editNameModalOpen = true;
      this.nameLocal = selectedSubscription.name;
    },

    async updateSubscriptionName() {
      const { nameLocal, selectedSubscription } = this;

      if (!nameLocal) return;

      this.updatingName = true;

      try {
        await this.SUBSCRIPTION_RENAME({
          id: selectedSubscription.id,
          payload: {
            name: nameLocal
          }
        });
        this.$toasted.global.success({
          message: `Updated subscription name to ${nameLocal}.`
        });
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.updatingName = false;
        this.editNameModalOpen = false;
      }
    },

    redirect() {
      window.location.href = `https://${this.store.shopify_url}`;
    }
  },
  head() {
    return { title: this.store.shop_name || 'Subscriptions' };
  }
};
</script>

<template>
  <section class="c-subscritionDetails">
    <div>
      <v-container v-if="!subscriptionsLoaded" class="text-center">
        <v-progress-circular indeterminate color="primary"></v-progress-circular>
      </v-container>

      <div v-else-if="subscriptionsLoaded && !subscriptions.length" class="c-noSubscriptions">
        <h2 class="c-noSubscriptions__text">
          {{ atc('notices.noselectedSubscriptions') }}
        </h2>
        <v-btn class="c-noSubscriptions__button" @click.prevent="redirect">
          {{ atc('actions.shopNow') }}
        </v-btn>
      </div>

      <div v-else>
        <v-container>
          <v-row>
            <v-col>
              <h1 v-if="selectedSubscription.id" class="text-h4 mb-2">
                {{ atc('labels.subscription') }}: {{ selectedSubscription.name }} (#{{ selectedSubscription.id }})
                <v-btn inline text color="primary" @click="editSubscriptionName">{{ atc('actions.rename') }} </v-btn>
              </h1>

              <v-chip
                v-if="selectedSubscription && selectedSubscription.status !== 'active'"
                class="ma-2 text-uppercase c-subscriptionInactiveTag"
                color="error"
                outlined
              >
                {{ selectedSubscription.status }}
              </v-chip>
            </v-col>
          </v-row>
          <v-row>
            <v-col sm="5" col="12" md="4">
              <div class="c-subscription__blocks">
                <h5 class="caption mb-2 d-sm-none">Payment and Shipping</h5>
                <v-tabs
                  v-if="selectedSubscription.status.toLowerCase() !== 'cancelled'"
                  v-model="mode"
                  active-class="font-weight-bold"
                >
                  <v-tab key="0" class="text-button text-capitalize"> {{ atc('labels.subscription') }} </v-tab>
                  <v-tab key="1" class="text-button text-capitalize"> {{ atc('labels.nextCharge') }} </v-tab>
                </v-tabs>
                <subscription-settings />
              </div>
            </v-col>
            <v-col col="12" sm="7" md="8" class="order-first order-sm-0">
              <h5 class="mb-2 d-sm-none caption">Products</h5>
              <subscription-product v-for="item in selectedSubscription.items" :key="item.id" :product="item" />
            </v-col>
          </v-row>
        </v-container>
      </div>
    </div>
    <drawer title="Update Subscription Name" :show="editNameModalOpen" @close="editNameModalOpen = false">
      <template v-slot:button>
        <v-btn :loading="updatingName" @click="updateSubscriptionName">{{ atc('actions.save') }}</v-btn>
      </template>

      <v-row class="mt-2">
        <v-col width="200">
          <v-text-field v-model="nameLocal" label="Subscription Name" />
        </v-col>
      </v-row>
    </drawer>
  </section>
</template>

<style lang="scss">
.u-disableExpiredChanges {
  pointer-events: none;
  opacity: 0.5;
}

.c-subscription {
  margin: 0 auto;
  width: 100%;

  @media (min-width: 420px) {
    width: 400px;
  }

  @media (min-width: 960px) {
    width: 100%;
  }
}

.c-subscription__outer {
  background-color: #f7f9fb;
}

.c-subscription__intro {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 20px 20px;

  @media (min-width: 960px) {
    padding: 20px 32px;
  }

  @media (min-width: 1280px) {
    padding: 20px 0;
  }
}

.c-subscription__name {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 20px 20px;

  @media (min-width: 960px) {
    padding: 20px 32px;
  }

  @media (min-width: 1280px) {
    padding: 20px 0;
  }

  @media (min-width: 786px) {
    display: grid;
    column-gap: 2rem;
  }
}

.c-subscription__blocks {
  min-width: 264px;
  background-color: #eceff1 !important;
  .v-slide-group__prev--disabled {
    display: none !important;
  }
}
.c-subscription__loader {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 300px;
}
</style>
