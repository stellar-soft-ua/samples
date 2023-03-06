<template>
    <div class="c-chargeButtonContainer">
        <v-btn
            v-if="chargeNowEnabled"
            tile
            color="primary"
            @click="chargeNow"
        >
            {{ atc('actions.chargeNow') }}
        </v-btn>
        <v-btn
            v-else
            tile
            large
            color="primary"
            @click="shipTomorrow"
        >
            {{ atc('actions.shipTomorrow') }}
        </v-btn>
        <v-btn
            tile
            outlined
            large
            class="ml-1"
            @click="skipNextShipment"
        >
            {{ atc('actions.skipShipment') }}
        </v-btn>
    </div>
</template>
<script>
import moment from 'moment';
import { mapState, mapActions } from 'vuex';

export default {
    computed: {
        ...mapState({
            chargeNowEnabled: 'store.chargeNowEnabled',
            activeLanguageCode: 'translations.activeLanguageCode',
            selectedSubscription: 'subscriptions.selectedSubscription',
        }),
    },
    methods: {
        ...mapActions('subscriptions', [
            'SUBSCRIPTION_SKIP_NEXT_SHIPMENT',
            'SUBSCRIPTION_UPDATE',
            'SUBSCRIPTION_ACTION',
        ]),

        async skipNextShipment() {
            this.showLoader(true);
            try {
                await this.SUBSCRIPTION_ACTION({
                    id: this.selectedSubscription.id,
                    action: 'skip',
                });
                this.$toasted.global.success({
                    message: `Next subscription shipment skipped`,
                });
            } catch (e) {
                console.error('updateSubscription: ', e.message);
                this.$toasted.global.error({
                    message: e.message,
                });
            } finally {
                this.showLoader(false);
            }
        },

        async chargeNow() {
            this.showLoader(true);
            try {
                await this.SUBSCRIPTION_ACTION(this.selectedSubscription.id);
                this.$toasted.global.success({ message: `Charge Created` });
            } catch (e) {
                this.$toasted.global.error({ message: e });
            } finally {
                this.showLoader(false);
            }
        },

        async shipTomorrow() {
            this.showLoader(true);
            const tomorrow = moment().add(1, 'days');
            try {
                await this.SUBSCRIPTION_UPDATE({
                    id: this.selectedSubscription.id,
                    payload: {
                        next_charge_at: tomorrow,
                    },
                });
                this.$toasted.global.success({ message: this.atc('notices.nextShipmentTomorrow') });
            } catch (e) {
                console.error('shipTomorrow e: ', e);
                this.$toasted.global.error({ message: e.message });
            } finally {
                this.showLoader(false);
            }
        },
    },
};
</script>
<style lang="scss">
.c-chargeButtonContainer {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 5px;
}
</style>