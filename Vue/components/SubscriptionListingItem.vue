<script>
import Vue from 'vue';
import {mapActions, mapState} from 'vuex';
import moment from 'moment-timezone';
import {windowSizes} from '~/mixins/windowSizes.js';

export default Vue.extend({
    mixins: [windowSizes],

    props: {
        subscription: {
            type: Object,
            required: true
        },
        index: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            moment,
            loaded: false,
            actionLoading: false,
            reactivating: false,

            editNameModalOpen: false,
            customer: false,

            nameLocal: '',
            updatingName: false
        };
    },
    computed: {
        ...mapState('api', ['storeDomain'])
    },
    methods: {
        ...mapActions('customers', ['CUSTOMER_GET', 'CUSTOMER_GET_CHARGES', 'CUSTOMER_GET_REFUNDS', 'CUSTOMER_GET_ME']),

        ...mapActions('subscriptions', [
            'SUBSCRIPTIONS_LIST',
            'SUBSCRIPTION_UPDATE',
            'SUBSCRIPTION_ACTION',
            'SUBSCRIPTION_RENAME'
        ]),

        cancelSubscription(id) {
            this.$router.push({
                name: 'cancel-id',
                params: {id}
            });
        },

        editSubscriptionName(subscription) {
            this.editNameModalOpen = true;
            this.nameLocal = subscription.name;
        },

        async updateSubscriptionName() {
            const {nameLocal, subscription} = this;

            if (!nameLocal) return;

            this.updatingName = true;

            try {
                await this.SUBSCRIPTION_RENAME({
                    id: subscription.id,
                    payload: {
                        name: nameLocal
                    }
                });
                this.$toasted.global.success({
                    message: this.atc('notices.updatedSubscriptionNameTo', nameLocal)
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

        async reactivateSubscription(subscription) {
            this.reactivating = true;
            try {
                await this.SUBSCRIPTION_ACTION({
                    id: subscription.id,
                    action: 'activate'
                });

                this.$toasted.global.success({
                    message: this.atc('notices.subscriptionReactivated', subscription.id)
                });
            } catch (e) {
                console.error('updateSubscription: ', e.message);
                this.$toasted.global.error({
                    message: e.message
                });
            } finally {
                this.reactivating = false;
            }
        },

        getRowProperty(items) {
            if (this.windowWidth < 600) {
                return 'unset';
            } else {
                return '4';
            }
        },

        async triggerSubscriptionAction(action) {
            const {subscription} = this;
            try {
                await this.SUBSCRIPTION_ACTION({id: subscription.id, action});
                this.$toasted.global.success({
                    message: this.atc('subscriptionActionSuccessful', action)
                });
            } catch (e) {
                console.error('updateSubscription: ', e.message);
                this.$toasted.global.error({
                    message: e.message
                });
            } finally {
                this.actionLoading = false;
            }
        }
    }
});
</script>

<template>
    <v-card>
        <v-card-text>
            <v-chip
                v-if="subscription && subscription.status.toLowerCase() !== 'active'"
                class="ma-2 text-uppercase c-subscriptionInactiveTag"
                color="error"
                outlined
            >
                {{ subscription.status }}
            </v-chip>

            <div class="c-subscription__container">
                <div class="c-subscription__details">
                    <div>
                        <h3 class="mb-2">{{ atc('labels.subscriptionName') }}</h3>
                        <p class="c-subscription__nameWrap">
              <span class="c-subscription__name pa-0 text-subtitle-2 d-flex align-baseline justify-space-between">
                {{ subscription.name }}
                <v-icon tag="span" class="pa-1" size="small" @click.prevent="editSubscriptionName(subscription)"
                >mdi-pencil</v-icon
                >
              </span>

                            <!-- <a
                              href=""
                              class="text-subtitle-2 font-weight-bold text-uppercase text-decoration-none"
                              @click.prevent="editSubscriptionName(subscription)"
                              >{{ atc('actions.rename') }}</a
                            > -->
                        </p>

                        <drawer-wrap title="Update Subscription Name" :show="editNameModalOpen"
                                     @close="editNameModalOpen = false">
                            <template v-slot:button>
                                <v-btn :loading="updatingName" @click="updateSubscriptionName">{{
                                        atc('actions.save')
                                    }}
                                </v-btn>
                            </template>

                            <v-row class="mt-2">
                                <v-col width="200">
                                    <v-text-field v-model="nameLocal" :label="atc('labels.subscriptionName')"/>
                                </v-col>
                            </v-row>
                        </drawer-wrap>
                    </div>
                    <div>
                        <h3 class="mb-2">{{ atc('labels.subscriptionId') }}</h3>
                        <p class="text-subtitle-2">{{ subscription.id }}</p>
                    </div>
                    <div>
                        <h3 class="mb-2">{{ atc('labels.createdOn') }}</h3>
                        <p class="text-subtitle-2">
                            {{ subscription.created_at | date }}
                        </p>
                    </div>
                    <div>
                        <h3 class="u-mb-2">{{ atc('labels.nextOrder') }}</h3>
                        <p class="text-subtitle-2">
                            {{ subscription.next_charge_at | date }}
                        </p>
                    </div>
                    <div>
                        <h3 class="u-mb-2">{{ atc('labels.orderTotal') }}</h3>
                        <p class="text-subtitle-2">
                            {{ formatMoney(subscription.total_price) }}
                        </p>
                    </div>
                </div>
            </div>

            <v-divider class="my-3"/>

            <div class="c-subscriptionItem__container pa-2">
                <div v-for="item in subscription.items" :key="item.id" class="c-subscription__item">
                    <img
                        class="c-orderItem__thumbnail"
                        :src="item.variant_image"
                        :alt="item.title"
                        onerror="this.style.display='none'"
                    />
                    <div class="c-orderItem__info px-2">
                        <h4 class="font-weight-medium text-subtitle-1">
                            {{ item.title }}
                            <span v-if="item.variant_title && item.variant_title !== '-'"> - {{
                                    item.variant_title
                                }} </span>
                        </h4>
                        <p class="ma-0">Quantity: {{ item.quantity }}</p>
                        <p class="ma-0">
                            {{ atc('labels.shipsEvery') }}

                            {{
                                `${subscription.delivery.interval} ${intervalUnitDisplay(
                                    subscription.delivery.frequency.toLowerCase(), subscription.delivery.interval
                                )}`
                            }}
                        </p>
                    </div>
                </div>
                <div class="c-subscription__ctaContainer d-flex align-center justify-center">
                    <v-btn
                        v-if="subscription.status.toLowerCase() === 'active'"
                        :to="{
              name: 'subscriptions-id',
              params: { id: subscription.id }
            }"
                        color="primary"
                        class="mb-2"
                        tile
                        block
                        max-width="350px"
                        height="48"
                    >
                        {{ atc('actions.editSubscription') }}
                    </v-btn>
                    <v-btn
                        v-if="subscription.status.toLowerCase() !== 'active'"
                        :loading="reactivating"
                        color="green"
                        outlined
                        tile
                        block
                        height="48"
                        @click.prevent="reactivateSubscription(subscription)"
                    >
                        {{ atc('actions.reactivateSubscription') }}
                    </v-btn>
                </div>
            </div>
        </v-card-text>
    </v-card>
</template>

<script>
import Vue from 'vue';
import {mapActions, mapState} from 'vuex';
import moment from 'moment-timezone';
import {windowSizes} from '~/mixins/windowSizes.js';

export default Vue.extend({
    mixins: [windowSizes],

    props: {
        subscription: {
            type: Object,
            required: true
        },
        index: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            moment,
            loaded: false,
            actionLoading: false,
            reactivating: false,

            editNameModalOpen: false,
            customer: false,

            nameLocal: '',
            updatingName: false
        };
    },
    computed: {
        ...mapState('api', ['storeDomain'])
    },
    methods: {
        ...mapActions('customers', ['CUSTOMER_GET', 'CUSTOMER_GET_CHARGES', 'CUSTOMER_GET_REFUNDS', 'CUSTOMER_GET_ME']),

        ...mapActions('subscriptions', [
            'SUBSCRIPTIONS_LIST',
            'SUBSCRIPTION_UPDATE',
            'SUBSCRIPTION_ACTION',
            'SUBSCRIPTION_RENAME'
        ]),

        cancelSubscription(id) {
            this.$router.push({
                name: 'cancel-id',
                params: {id}
            });
        },

        editSubscriptionName(subscription) {
            this.editNameModalOpen = true;
            this.nameLocal = subscription.name;
        },

        async updateSubscriptionName() {
            const {nameLocal, subscription} = this;

            if (!nameLocal) return;

            this.updatingName = true;

            try {
                await this.SUBSCRIPTION_RENAME({
                    id: subscription.id,
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

        async reactivateSubscription(subscription) {
            this.reactivating = true;
            try {
                const subscriptionUpdated = await this.SUBSCRIPTION_ACTION({
                    id: subscription.id,
                    action: 'activate'
                });

                // this.subscription = { ...subscriptionUpdated };
                // await this.getSubscription(subscription.id);

                this.$toasted.global.success({
                    message: `Subscription #${subscription.id} reactivated`
                });
            } catch (e) {
                console.error('updateSubscription: ', e.message);
                this.$toasted.global.error({
                    message: e.message
                });
            } finally {
                this.reactivating = false;
            }
        },

        getRowProperty(items) {
            if (this.windowWidth < 600) return 'unset';

            return '4';
        },
        async triggerSubscriptionAction(action) {
            const {subscription} = this;
            try {
                await this.SUBSCRIPTION_ACTION({id: subscription.id, action});

                // reload until endpoint returns updated sub
                // await this.getSubscription(subscription.id);

                this.$toasted.global.success({
                    message: `Subscription Action: '${action}' successful`
                });
            } catch (e) {
                console.error('updateSubscription: ', e.message);
                this.$toasted.global.error({
                    message: e.message
                });
            } finally {
                this.actionLoading = false;
            }
        }
    }
});
</script>

<style lang="scss">
.c-subscriptions {
    position: relative;
    padding: 20px;
    margin: 20px 0;
}

.c-subscription__details {
    display: flex;
    justify-content: space-around;

    @media (max-width: 767px) {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    > div {
        flex: 1;
        padding: 0 12px;
    }

    h3 {
        font-size: 16px;
        font-weight: bold;

        @media (max-width: 767px) {
            font-size: 15px;
        }
    }

    p {
        @media (max-width: 767px) {
            font-size: 14px;
        }
    }
}

.c-subscription__container {
    display: grid;
    // grid-template-columns: 70% 30%;

    @media (max-width: 1023px) {
        grid-template-columns: 1fr;
    }

    @media (max-width: 767px) {
        grid-template-columns: 1fr;
    }
}

.c-subscription__nameLink {
    margin-left: 5px;
    // font-family: $font-primary-bold;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.c-subscription__divider {
    display: block;
    height: 1px;
    margin: 30px 0;
    border: 0;
    // border-top: 1px solid $color-light;
}

.c-subscriptionItem__container {
    display: grid;
    grid-template-columns: 1fr;
    row-gap: 10px;

    @media (min-width: 768px) {
        grid-template-columns: 1fr 1fr;
    }

    @media (min-width: 1024px) {
        grid-template-columns: 33% 33% 33%;
        row-gap: 30px;
    }
}

.c-subscription__ctaContainer {
    @media (min-width: 1024px) {
        grid-column: 3;
        grid-row-end: auto;
    }

    button {
        margin-bottom: 10px;
        @media (max-width: 767px) {
            min-width: 200px;
            height: 45px;
            margin: auto;
            margin-bottom: 10px;
        }
    }
}

.c-subscription_btn--outline {
    // color: $color-primary;
    background-color: transparent;
    // border: 1px solid $color-primary;
}

.c-subscription__helperInfo {
    display: none !important;

    @media (min-width: 1024px) {
        display: block !important;
    }
}

.c-subscription__helperInfo--mobile {
    display: block !important;

    @media (min-width: 1024px) {
        display: none !important;
    }
}

.c-subscription__item {
    display: flex;
}

.c-orderItem__thumbnail {
    width: auto;
    height: 90px;
}

.c-orderItem__info {
    h4 {
        font-size: 16px;
        font-weight: normal;
    }

    p {
        margin: 4px 0;
        font-size: 13px;
    }
}

.c-subscriptions--inactive::before {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    content: '';
    opacity: 0.5;
}
</style>
