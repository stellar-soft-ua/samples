<script>
import moment from 'moment';
import { mapActions, mapState } from 'vuex';

export default {
  props: {
    show: {
      type: Boolean,
      default: false
    }
  },
  data: () => {
    return {
      loading: false,
      shipmentDateLocal: false,
      min: moment().add(1, 'day').toISOString()
    };
  },
  computed: {
    ...mapState('subscriptions', ['selectedSubscription']),

    ...mapState('translations', ['activeLanguageCode']),

    shipmentDate() {
      const { selectedSubscription } = this;
      if (!selectedSubscription) return false;
      if (!moment(selectedSubscription.next_charge_at, 'YYYY-MM-DD').isValid()) return false;
      return moment(selectedSubscription.next_charge_at, 'YYYY-MM-DD').format('MMM D');
    },

    shipmentDateWithYear() {
      const { selectedSubscription } = this;
      if (!selectedSubscription) return false;
      if (!moment(selectedSubscription.next_charge_at, 'YYYY-MM-DD').isValid()) return false;
      return moment(selectedSubscription.next_charge_at, 'YYYY-MM-DD');
    },

    nextShipmentDates() {
      const { selectedSubscription, shipmentDateWithYear } = this;
      const { delivery } = selectedSubscription;
      if (!selectedSubscription || !selectedSubscription.delivery) return false;

      const { frequency, interval } = delivery;

      return [
        moment(shipmentDateWithYear).add(interval, frequency),
        moment(shipmentDateWithYear).add(interval * 2, `${frequency.toLowerCase()}s`)
      ];
    }
  },

  watch: {
    activeLanguageCode: {
      immediate: true,
      handler: function (newVal) {
        moment.locale(newVal);
      }
    },

    selectedSubscription(newSub) {
      this.shipmentDateLocal = moment(newSub.next_charge_at).format().split('T')[0];
    }
  },

  mounted() {
    const { selectedSubscription } = this;
    this.shipmentDateLocal = moment(selectedSubscription.next_charge_at).format().split('T')[0];
  },

  methods: {
    ...mapActions('subscriptions', ['SUBSCRIPTION_UPDATE']),

    async saveShipmentDate() {
      const { shipmentDateLocal, selectedSubscription } = this;
      this.showLoader(true);

      try {
        await this.SUBSCRIPTION_UPDATE({
          id: selectedSubscription.id,
          payload: {
            next_charge_at: moment(shipmentDateLocal).utc()
          }
        });
        this.$toasted.global.success({
          message: this.atc('notices.updatedNextShipmentDate', shipmentDateLocal)
        });
      } catch (e) {
        this.$toasted.global.error({
          message: e.message
        });
      } finally {
        this.showLoader(false);
        this.$emit('close');
      }
    }
  }
};
</script>

<template>
  <div>
    <p class="font-weight-medium body-1">{{ atc('labels.scheduleNewShipment') }}</p>

    <v-date-picker
      v-if="shipmentDateLocal && min"
      v-model="shipmentDateLocal"
      full-width
      :min="min"
      elevation="1"
    ></v-date-picker>

    <v-container v-if="nextShipmentDates" class="text-left">
      <h3 class="h5 font-weight-bold mb-3 grey--text text--darken-3">
        {{ atc('labels.nextShipments') }}
      </h3>
      <div v-for="(date, index) in nextShipmentDates" :key="index" class="mb-3 mb-3">
        <v-icon>mdi-package-variant</v-icon>
        <span class="body-1">{{ date | date }}</span>
      </div>
      <v-btn block tile color="primary" @click="saveShipmentDate">{{ atc('actions.save') }}</v-btn>
    </v-container>
  </div>
</template>

<style lang="scss">
.v-date-picker-table th {
  border: none;
}

.v-date-picker-table td {
  border: none;
  padding: 0;
}
</style>
