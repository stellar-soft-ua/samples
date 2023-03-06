<template>
  <section class="c-address">
    <v-container>
      <v-row class="mb-4">
        <v-col>
          <h1 class="text-h4 mb-2">{{ atc('labels.addresses') }}</h1>
          <v-btn color="primary" class="float-right text-button" @click="handleNewAddressClick()"
            >Create new Adderss</v-btn
          >
        </v-col>
      </v-row>
      <v-row>
        <v-col v-if="loading" class="text-center">
          <v-progress-circular indeterminate color="primary"></v-progress-circular>
        </v-col>
        <v-col v-for="address in addressList" :key="address.id" cols="12" sm="4" @click="handleAddressSelect(address)">
          <v-card class="c-address__card">
            <v-card-title class="d-flex align-center justify-space-between">
              <h6 class="text-h6">{{ address.first_name }} {{ address.last_name }}</h6>
              <v-icon>mdi-pencil</v-icon>
            </v-card-title>
            <v-divider class="my-3" />
            <v-card-text>
              <p v-if="address.company" class="mb-1 body-1">{{ address.company }}</p>
              <p class="mb-1 body-1">{{ address.address1 }}</p>
              <p class="mb-1 body-1">{{ address.address2 }}</p>
              <p class="mb-1 body-1">{{ address.city }}, {{ address.province }}</p>
              <p class="mb-1 body-1">{{ address.country }}, {{ address.zip }}</p>
            </v-card-text>

            <v-card-text v-if="address.matching_subscriptions_count > 0" class="grey--text body-1">
              {{ address.matching_subscriptions_count }} subscriptions have this address
            </v-card-text>
          </v-card>
        </v-col>
        <v-col v-if="!loading && !addressList.length">
          <p>{{ atc('notices.noAddressesAvailable') }}</p>
        </v-col>
      </v-row>

      <drawer
        :title="addNewAddress ? 'Add New Address' : 'Update Address'"
        :show="openAddressDrawer"
        @close="closeAddressDrawer()"
      >
        <v-container>
          <address-form
            v-if="addNewAddress"
            :initial-address="selectedAddress"
            :updating="updatingAddress"
            @submitAddress="createAddress"
          />
          <address-form
            v-else
            :initial-address="selectedAddress"
            :updating="updatingAddress"
            @submitAddress="updateAddress"
          />
        </v-container>
      </drawer>
    </v-container>
  </section>
</template>

<script>
import { mapActions, mapState } from 'vuex';
import { EventBus } from '~/utils/event-bus';
export default {
  data() {
    return {
      addressList: [],
      selectedAddress: {},
      openAddressDrawer: false,
      updatingAddress: false,
      addNewAddress: false,
      loading: false
    };
  },
  computed: { ...mapState('store', ['store']) },
  async mounted() {
    try {
      await this.getAddressList();
    } catch (e) {
      console.error(e);
      this.$toasted.global.error({
        message: e.message
      });
    }
  },
  destroyed() {
    EventBus.$emit('abortRequest');
  },
  methods: {
    ...mapActions('address', ['GET_ADDRESS', 'DELETE_ADDRESS', 'UPDATE_ADDRESS', 'CREATE_ADDRESS']),
    handleAddressSelect(address) {
      this.openAddressDrawer = false;
      this.selectedAddress = address;
      this.openAddressDrawer = true;
    },
    closeAddressDrawer() {
      this.selectedAddress = {};
      this.openAddressDrawer = false;
      this.addNewAddress = false;
    },
    handleNewAddressClick() {
      this.selectedAddress = {};
      this.openAddressDrawer = true;
      this.addNewAddress = true;
    },
    async getAddressList() {
      try {
        this.loading = true;
        const { data } = await this.GET_ADDRESS();
        this.addressList = data;
      } catch (e) {
        console.error(e);
      } finally {
        this.loading = false;
      }
    },
    async createAddress(address) {
      try {
        this.showLoader(true);
        this.updatingAddress = true;
        await this.CREATE_ADDRESS({
          ...address
        });
        await this.getAddressList();
      } catch (e) {
        this.$toasted.global.error({ e });
      } finally {
        this.showLoader(false);
        this.updatingAddress = false;
      }
    },
    async updateAddress(address, applyToAllSubscriptions) {
      try {
        this.showLoader(true);

        this.updatingAddress = true;
        await this.UPDATE_ADDRESS({
          payload: address,
          id: this.selectedAddress.id,
          applyToAllSubscriptions
        });
        await this.getAddressList();
      } catch (e) {
        this.$toasted.global.error({ e });
      } finally {
        this.showLoader(false);
        this.updatingAddress = false;
      }
    }
  },
  head() {
    return { title: this.store.shop_name || 'Address' };
  }
};
</script>

<style lang="scss">
.c-address {
  .c-address__card {
    height: 100%;
  }
}
</style>
