<script>
import { mapState, mapGetters } from 'vuex';
import { windowSizes } from '~/mixins/windowSizes.js';

export default {
  mixins: [windowSizes],

  data: () => {
    return {
      mobileNav: null
    };
  },

  computed: {
    ...mapState('subscriptions', ['subscriptions']),
    ...mapState('translations', ['translationList', 'translations', 'activeLanguageCode']),
    ...mapGetters('translations', ['activeLanguageName', 'activeLanguageNativeName']),

    firstSubscriptionId() {
      return this.subscriptions.find((item) => item.status === 'active')?.id || false;
    },

    shopifyAccountUrl() {
      const { store } = this;
      if (!store) return;
      return `https://${store.shopify_url}/account`;
    },
    mobileNavItems() {
      const items = [
        {
          value: 'shopify-account',
          text: 'Shopify Account'
        },
        {
          value: 'index',
          text: 'Subscriptions'
        },
        {
          value: 'history',
          text: 'Subscription History'
        },
        {
          value: 'address',
          text: 'Addresses'
        }
      ];

      return items;
    }
  },

  watch: {
    mobileNav(newVal) {
      if (newVal) {
        if (newVal === 'shopify-account') {
          window.location.href = this.shopifyAccountUrl;
        } else if (newVal === 'subscriptions-id' && this.firstSubscriptionId) {
          this.$router.push({
            name: 'subscriptions-id',
            params: {
              id: this.firstSubscriptionId
            }
          });
        } else {
          this.$router.push({
            name: newVal
          });
        }
      }
    }
  },
  async created() {
    this.mobileNav = this.$route.name;
  }
};
</script>

<template>
  <v-app-bar app flat class="white c-header" scroll-off-screen height="55px">
    <v-container class="d-md-block d-none">
      <nav class="d-flex align-center">
        <a class="text-subtitle-1 float-left text-decoration-none absolute" :href="shopifyAccountUrl">
          <v-icon left color="primary"> mdi-chevron-left </v-icon>
          {{ atc('labels.account') }}
        </a>

        <div v-if="store" class="text-center c-navigationMenu d-flex mx-auto">
          <nuxt-link
            class="c-header__link mx-4 text-subtitle-1"
            nuxt
            exact
            :class="{ 'is-exact-match': $route.name === 'index' }"
            :to="{ name: 'index' }"
          >
            {{ atc('labels.subscriptions') }}
          </nuxt-link>

          <nuxt-link
            class="c-header__link mx-4 text-subtitle-1"
            :class="{ 'is-exact-match': $route.name === 'history' }"
            :to="{ name: 'history' }"
            >{{ atc('labels.subscriptionHistory') }}
          </nuxt-link>

          <nuxt-link
            class="c-header__link mx-4 text-subtitle-1"
            :class="{ 'is-exact-match': $route.name === 'address' }"
            :to="{ name: 'address' }"
          >
            {{ atc('labels.addresses') }}
          </nuxt-link>
        </div>
      </nav>
    </v-container>

    <v-container class="d-md-none d-block py-4 c-header__navigation">
      <v-select v-model="mobileNav" solo hide-details class="elevation-0" :items="mobileNavItems" />
    </v-container>
  </v-app-bar>
</template>

<style lang="scss">
.c-header {
  .v-toolbar__content {
    padding: 0 16px;
  }
  .c-header__link {
    color: var(--v-secondary-base) !important;
    border-radius: 0;
    text-decoration: none;
    padding: 16px 0;
    &.is-exact-match {
      border-bottom: 2px solid var(--v-primary-base);
      padding: 14px 0;
    }
  }
  .c-header__navigation {
    max-width: 300px;
    @media (min-width: 960px) {
      max-width: auto;
    }
  }
}

.v-app-bar.v-app-bar--fixed {
  height: auto !important;
  position: relative !important;
  flex-grow: 0 !important;
  flex-shrink: 0 !important;
}

.v-navigation-drawer--fixed {
  z-index: 999 !important;
}
</style>
