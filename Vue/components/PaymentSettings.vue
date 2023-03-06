<template>
  <div class="text-center">
    <div v-html="atc('notices.paymentDetailsHtml')" />
    <v-btn color="primary" block @click="sendUpdatePaymentEmail">{{ atc('actions.sendEmail') }}</v-btn>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';

export default {
  computed: {
    ...mapState('subscriptions', ['selectedSubscription'])
  },

  methods: {
    ...mapActions('subscriptions', ['SUBSCRIPTION_SEND_UPDATE_PAYMENT_METHOD_EMAIL']),

    async sendUpdatePaymentEmail() {
      const { selectedSubscription } = this;
      this.showLoader(true);
      try {
        await this.SUBSCRIPTION_SEND_UPDATE_PAYMENT_METHOD_EMAIL(selectedSubscription.id);
        this.$toasted.global.success({ message: this.atc('notices.paymentMethodEmailSent') });
      } catch (e) {
        console.error('sendUpdatePaymentEmail: ', e.message);
        this.$toasted.global.error({ message: e.message });
      } finally {
        this.showLoader(false);
      }
    }
  }
};
</script>
