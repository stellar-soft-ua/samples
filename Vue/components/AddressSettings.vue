<template>
    <div>
        <address-form :initial-address="shippingAddress" @submitAddress="saveAddress" />
    </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';

export default {
    props: {
        data: {
            default: () => {},
            type: Object
        },
        charge: {
            default: () => {},
            type: Object
        }
    },
    computed: {
        ...mapState('subscriptions', ['selectedSubscription']),
        shippingAddress() {
            return this.data?.shipping || this.selectedSubscription?.shipping;
        }
    },

    methods: {
        ...mapActions('subscriptions', ['SUBSCRIPTION_UPDATE_SHIPPING', 'GIFT_NEXT_CHARGE']),

        async saveAddress(address) {
            this.showLoader(true);
            const { id } = this.selectedSubscription;
            try {
                if (this.data?.shipping) {
                    await this.GIFT_NEXT_CHARGE({ id, payload: address });
                    this.$toasted.global.success({ message: this.atc('notices.nextShipmentAddress') });
                } else {
                    await this.SUBSCRIPTION_UPDATE_SHIPPING({ id, payload: address });
                    this.$toasted.global.success({ message: `Address updated.` });
                }
                this.$emit('addressChanged');
            } catch (e) {
                this.$toasted.global.error({ message: e.message });
            } finally {
                this.showLoader(false);
            }
        }
    }
};
</script>