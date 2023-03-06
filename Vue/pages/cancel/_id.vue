<script>
import { mapState, mapActions, mapMutations } from 'vuex';

export default {
  data() {
    return {
      drawerDeliveryDateOpen: false,
      drawerProductsOpen: false,
      cancelSubscriptionUpdating: false,
      comments: '',

      cancelling: false,
      cancellationReasons: false,
      selectedCancellationReason: false,
      loadingCancellationReasons: false
    };
  },
  computed: {
    ...mapState('store', ['store'])
  },

  async mounted() {
    const subscriptionId = this.$route.params.id;

    this.loadCancellationReasons();

    try {
      const subscription = await this.SUBSCRIPTION_GET(subscriptionId);
      this.subscription = { ...subscription };
      this.setSelectedSubscription(subscription);
    } catch (e) {
      this.$toasted.global.error({
        message: e.message
      });
    }
  },

  methods: {
    ...mapActions('subscriptions', ['CANCEL_SUBSCRIPTION', 'SUBSCRIPTION_GET', 'SUBSCRIPTION_ACTION']),
    ...mapMutations('subscriptions', ['setSelectedSubscription']),

    ...mapActions('store', ['GET_STORE_SETTING']),

    async loadCancellationReasons() {
      this.loadingCancellationReasons = true;
      try {
        const cancellationReasons = await this.GET_STORE_SETTING('cancellation_reasons');

        if (!cancellationReasons.value || !cancellationReasons.value[0]) {
          this.cancellationReasons = false;
        } else {
          this.cancellationReasons = cancellationReasons.value;
        }
      } catch (e) {
        console.error(e);
      } finally {
        this.loadingCancellationReasons = false;
      }
    },

    async handleCancelSubscription() {
      const { subscription } = this;
      this.cancelSubscriptionUpdating = true;
      this.cancelling = true;
      this.saveAddress(true);
      try {
        // await this.CANCEL_SUBSCRIPTION({
        // 	subscriptionId: subscription.id,
        // 	cancellationReasonId: this.selectedReason.id,
        // });
        await this.SUBSCRIPTION_ACTION({
          id: subscription.id,
          action: 'cancel',
          payload: {
            cancellation_reason: `${this.reason.reason} - ${this.subReason.reason} -
${this.subSubReason.reason}`.replaceAll('- undefined', ''),
            cancellation_comment: this.comments
          }
        });
        this.$toasted.global.success({
          message: this.atc('notices.subscriptionCancelled')
        });
        this.$router.push({ name: 'index' });
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.saveAddress(false);
        this.cancelling = false;
        this.cancelSubscriptionUpdating = false;
      }
    },
    head() {
      return { title: this.store.shop_name || 'Cancel Subscription' };
    }
  }
};
</script>

<template>
  <section class="c-cancel">
    <v-container class="c-cancel" style="margin: 0 auto; max-width: 800px">
      <h1 class="c-cancel__title text-h4 text-center mb-3">
        {{ atc('labels.cancelSubscription') }}
      </h1>

      <v-card class="c-cancel__inner mb-4">
        <v-card-text class="pa-0">
          <h3 class="font-weight-medium text-body-2 ma-0 pa-8 pb-0 text-center w-80 mx-auto">
            {{ atc('notices.cancellationText') }}
          </h3>
        </v-card-text>

        <v-card-text v-if="loadingCancellationReasons" key="loading" align="center" class="px-8">
          <v-progress-circular indeterminate color="primary"></v-progress-circular>
        </v-card-text>

        <v-card-text class="pa-8">
          <v-radio-group v-model="reason" class="ma-0" hide-details>
            <div v-for="(item, index) in cancellationReasons && cancellationReasons['CUSTOMER']" :key="index">
              <v-radio class="my-2" :value="item" :label="item.reason"></v-radio>
              <v-radio-group v-model="subReason" class="ma-0" hide-details>
                <div v-if="reason && item.reason === reason.reason" class="ml-10">
                  <div v-for="(subItem, subIndex) in reason.children" :key="subIndex">
                    <v-radio class="my-2" :label="subItem.reason" :value="subItem"> </v-radio>
                    <v-radio-group v-model="subSubReason" class="ma-0" hide-details>
                      <div v-if="subReason && subReason.reason === subItem.reason" class="ml-10">
                        <v-radio
                          v-for="(subsubItem, subsubIndex) in subReason.children"
                          :key="subsubIndex"
                          class="my-2"
                          :label="subsubItem.reason"
                          :value="subsubItem"
                        >
                        </v-radio>
                      </div>
                    </v-radio-group>
                  </div>
                </div>
              </v-radio-group>
            </div>
          </v-radio-group>

          <v-textarea v-model="comments" class="mt-10" outlined label="Other"></v-textarea>
        </v-card-text>
      </v-card>

      <div class="c-cancel__buttonBox text-center">
        <v-btn
          color="primary"
          class="mr-4 mb-2"
          tile
          large
          :disabled="cancelling"
          @click="drawerDeliveryDateOpen = true"
          >{{ atc('actions.delayShipment') }}</v-btn
        >

        <v-btn class="mr-4 mb-2" color="primary" tile large :disabled="cancelling" @click="drawerProductsOpen = true">{{
          atc('actions.editProducts')
        }}</v-btn>

        <v-btn
          color="error"
          class="mb-2"
          outlined
          :disabled="loadingCancellationReasons || !(reason && reason.reason)"
          tile
          large
          :loading="cancelling"
          @click="handleCancelSubscription"
          >{{ atc('actions.cancel') }} <span class="d-none d-md-block">{{ atc('labels.subscription') }}</span></v-btn
        >
      </div>

      <drawer-delivery-date :show="drawerDeliveryDateOpen" @close="drawerDeliveryDateOpen = false" />

      <drawer-products :show="drawerProductsOpen" @close="drawerProductsOpen = false" />
    </v-container>
  </section>
</template>

<script>
import { mapActions, mapMutations } from 'vuex';

export default {
  data() {
    return {
      drawerDeliveryDateOpen: false,
      drawerProductsOpen: false,
      cancelSubscriptionUpdating: false,
      comments: '',
      cancellationReasons: {},
      cancelling: false,
      loadingCancellationReasons: false,
      reason: {},
      subReason: {},
      subSubReason: {}
    };
  },

  async mounted() {
    const subscriptionId = this.$route.params.id;
    if (!subscriptionId) {
      this.$router.push('/');
    }

    this.loadCancellationReasons();

    try {
      const subscription = await this.SUBSCRIPTION_GET(subscriptionId);
      this.subscription = { ...subscription };
      this.setSelectedSubscription(subscription);
    } catch (e) {
      this.$toasted.global.error({
        message: e.message
      });
    }
  },

  methods: {
    ...mapActions('subscriptions', ['CANCEL_SUBSCRIPTION', 'SUBSCRIPTION_GET', 'SUBSCRIPTION_ACTION']),
    ...mapMutations('subscriptions', ['setSelectedSubscription']),

    ...mapActions('store', ['GET_STORE_SETTING']),

    async loadCancellationReasons() {
      this.loadingCancellationReasons = true;
      try {
        const data = await this.GET_STORE_SETTING('cancellation_reasons');

        this.cancellationReasons = data.value;
      } catch (e) {
        console.error(e);
      } finally {
        this.loadingCancellationReasons = false;
      }
    },

    async handleCancelSubscription() {
      const { subscription } = this;
      this.cancelSubscriptionUpdating = true;
      this.cancelling = true;
      try {
        // await this.CANCEL_SUBSCRIPTION({
        // 	subscriptionId: subscription.id,
        // 	cancellationReasonId: this.selectedReason.id,
        // });
        await this.SUBSCRIPTION_ACTION({
          id: subscription.id,
          action: 'cancel',
          payload: {
            cancellation_reason: `${this.reason.reason} - ${this.subReason.reason} - ${this.subSubReason.reason}`.replace(
              '- undefined',
              ''
            ),
            cancellation_comment: this.comments
          }
        });
        this.$toasted.global.success({
          message: this.atc('notices.subscriptionCancelled')
        });
        this.$router.push({ name: 'index' });
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.cancelling = false;
        this.cancelSubscriptionUpdating = false;
      }
    }
  },
  watch: {
    reason() {
      this.subReason = {};
      this.subSubReason = {};
    },
    subReason() {
      this.subSubReason = {};
    }
  }
};
</script>
