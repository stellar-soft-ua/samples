<script>
import { mapActions } from 'vuex';

export default {
  layout: 'empty',
  data: () => ({
    window,
    showPassword: false,

    storeDomain: '',
    customer: '',
    email: '',
    token: '',

    loading: false
  }),
  mounted() {
    this.login();
  },
  methods: {
    ...mapActions('auth', ['LOGIN_CUSTOMER', 'LOGIN_CUSTOMER_BY_LINK']),
    ...mapActions('store', ['GET_STORE']),

    async loginByCredentials() {
      const { email, token, storeDomain, customer } = this;
      if (!email || !token || !storeDomain || !customer) {
        return;
      }

      const response = await this.LOGIN_CUSTOMER({
        email,
        token,
        customer,
        storeDomain,
      });
      window.localStorage.customerId = response.data.data.customer_id;
    },

    async loginByLink() {
      const storeDomain = window.location.hostname.split('.myshopify.com')[0];

      try {
        await this.LOGIN_CUSTOMER_BY_LINK({
          loginLink: this.$route.query.link,
          storeDomain,
        });

        window.localStorage.customerId = '';

      } catch (e) {
        console.log('error on login', e);
        if (e.status === 440) {
          window.location.href = `https://${window.location.hostname}/account/login?view=expiredLink`;
        } else if (e.status === 404) {
          window.location.href = `https://${window.location.hostname}/account/login?view=wrongLink`;
        } else {
          this.$toasted.global.error({
            message: e.message || e.data.message,
          });
        }
      }
    },

    async login() {
      this.loading = true;

      try {
        if (this.$route.query.link) await this.loginByLink();
        else await this.loginByCredentials();

        await this.GET_STORE();
        // await this.GET_STORE_INFO();

        this.$toasted.global
          .success({
            message: 'Successfully Logged In',
          })
          .goAway(2000);

        this.$router.push({
          name: 'index',
        });
      } catch (e) {
        console.error('LOGIN_CUSTOMER error: -', e);
        this.$toasted.global.error({
          message: e.message || e.data.message,
        });
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<template>
  <div class="text-center" style="width: 100%">
    <v-progress-circular indeterminate color="primary"></v-progress-circular>
  </div>
</template>
