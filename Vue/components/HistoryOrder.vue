<script>
import moment from 'moment';
import {mapState} from 'vuex';

export default {
    props: {
        order: {
            type: Object,
            required: true
        }
    },

    computed: {
        ...mapState('translations', ['activeLanguageCode']),

        orderStatus() {
            return this.order.status;
        },

        shippingPrice() {
            return this.order.customer_shipping_charges;
        }
    },

    watch: {
        activeLanguageCode: {
            immediate: true,
            handler: function (newVal) {
                moment.locale(newVal);
            }
        }
    },
};
</script>

<template>
    <v-expansion-panel>
        <v-expansion-panel-header>
      <span class="c-historyOrder__title">
        <span class="text-body-1">{{ order.order_date | date }} </span>
        <span v-if="order.customer_total_price" class="c-historyOrder__headline-price font-weight-bold text-body-1">{{
                formatMoney(order.customer_total_price)
            }}</span>
      </span>
        </v-expansion-panel-header>
        <v-expansion-panel-content class="c-historyOrderPanels">
            <v-container class="pl-0">
        <span v-if="order.status" class="text-subtitle-1 font-weight-bold">
          {{ atc('labels.orderStatus') }}: {{ order.status }}<br/>
        </span>
                <span v-if="order.id || order.shopify_order_id" class="text-subtitle-1 font-weight-bold">
          {{ atc('labels.orderId') }}: {{ order.id }}<br/>
        </span>
                <span v-if="order.name || order.name" class="text-subtitle-1 font-weight-bold">
          {{ atc('labels.orderName') }}: {{ order.name }}
        </span>
            </v-container>

            <v-simple-table v-if="order.items" class="c-historyOrder">
                <template v-slot:default>
                    <thead>
                    <tr>
                        <th class="text-subtitle-2 black--text pl-0">
                            <abbr title="Product">{{ atc('labels.product') }}</abbr>
                        </th>
                        <th class="text-center text-subtitle-2 black--text">
                            <abbr title="Price">{{ atc('labels.price') }}</abbr>
                        </th>

                        <th class="text-center text-subtitle-2 black--text">
                            <abbr title="Quantity">{{ atc('labels.quantity') }}</abbr>
                        </th>
                        <th class="text-center text-subtitle-2 black--text">
                            <abbr title="Price">{{ atc('labels.linePrice') }}</abbr>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in order.items" :key="index" class="pl-0">
                        <td v-if="item.title" class="pl-0 text-body-2">
                            {{ item.title }}
                            <span v-if="item.variant_title && item.variant_title !== '-'"> - {{
                                    item.variant_title
                                }}</span>
                        </td>
                        <td class="text-center text-body-2">
                            <span>{{ formatMoney(item.customer_price) }}</span>
                        </td>

                        <td v-if="item.quantity" class="text-center text-body-2">
                            {{ item.quantity }}
                        </td>

                        <td class="text-center text-body-2">
                            {{ formatMoney(item.customer_price * item.quantity) }}
                        </td>
                    </tr>

                    <tr class="c-orderHistoryRow--noBorder c-orderHistoryRow--separator">
                        <td class="text-subtitle-2 black--text pl-0">
                            {{ atc('labels.subtotal') }}
                        </td>
                        <td colspan="2"></td>
                        <td class="text-center text-body-2">
                            <span>{{ formatMoney(order.customer_subtotal_price) }}</span>
                        </td>
                    </tr>
                    <tr v-if="order && order.customer_shipping_charges" class="c-orderHistoryRow--noBorder">
                        <td class="pl-0">
                            <span class="text-subtitle-2 black--text">{{ atc('labels.shipping') }}</span>
                            <span v-if="order.shipping_title" class="font-weight-normal">&nbsp;{{
                                    order.shipping_title
                                }}</span>
                        </td>
                        <td colspan="2" class="text-center"></td>
                        <td class="text-center text-body-2">
                <span v-if="order && order.customer_shipping_charges">{{
                        formatMoney(order.customer_shipping_charges)
                    }}</span>
                            <span v-else>---</span>
                        </td>
                    </tr>

                    <tr class="c-orderHistoryRow--noBorder">
                        <td class="text-subtitle-2 black--text pl-0">
                            {{ atc('labels.taxes') }}
                        </td>

                        <td colspan="2"></td>

                        <td class="text-center text-body-2">
                            {{ formatMoney(order.customer_total_tax) }}
                        </td>
                    </tr>
                    <tr class="c-orderHistoryRow--noBorder">
                        <th class="text-subtitle-2 black--text pl-0">
                            {{ atc('labels.total') }}
                        </th>

                        <td colspan="2"></td>

                        <td class="text-center text-body-2">
                            {{ formatMoney(order.customer_total_price) }}
                        </td>
                    </tr>
                    </tbody>
                </template>
            </v-simple-table>
        </v-expansion-panel-content>
    </v-expansion-panel>
</template>

<script>
import moment from 'moment';

export default {
    props: {
        order: {
            type: Object,
            required: true
        }
    },

    computed: {
        orderStatus() {
            return this.order.status;
        },

        shippingPrice() {
            return this.order.customer_shipping_charges;
        },

        date() {
            return moment(this.order.order_date).format('MMM D, YYYY');
        }
    },
};
</script>

<style lang="scss">
.c-historyOrderPanels {
    border-top: 1px solid #e0e0e0;
}

.c-historyOrder__title {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100%;
}

.c-orderHistoryRow--noBorder {
    &:first-of-type {
        padding: 12px;
    }
}

.c-orderHistoryRow--noBorder td {
    border-bottom: none !important;
    height: 36px !important;
}

.c-historyOrder {
    th,
    td {
        border: none !important;
    }

    tbody {
        border: none !important;
    }

    .c-orderHistoryRow--separator {
        border-top: 1px solid #e0e0e0 !important;
    }
}
</style>
