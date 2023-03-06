<template>
    <v-card class="pa-4 c-subscriptionCard">
        <v-hover v-slot="{ hover }">
            <div class="d-flex align-center justify-space-between c-subscriptionCard__header" @click="toggle">
                <div>
                    <p v-if="title" class="text-overline grey--text text--darken-4 text-uppercase mb-0">{{ title }}</p>
                    <p
                        v-if="text"
                        :class="{ 'text-body-2 font-weight-normal': textSmall, 'text-subtitle-1 font-weight-bold': !textSmall }"
                    >
                        {{ text }}
                    </p>
                    <h3 v-else-if="html" class="text-body-1 font-weight-normal" v-html="html"></h3>
                </div>
                <div v-if="!noIcon">
                    <v-icon
                        v-if="mode === 'collapse' && open"
                        class="c-subscriptionCard__icon"
                        large
                        :class="{ 'c-subscriptionCard__icon--hover': hover }"
                    >mdi-minus
                    </v-icon
                    >
                    <v-icon
                        v-if="mode === 'collapse' && !open"
                        class="c-subscriptionCard__icon"
                        large
                        :class="{ 'c-subscriptionCard__icon--hover': hover }"
                    >mdi-plus
                    </v-icon
                    >
                    <v-icon
                        v-if="mode !== 'collapse'"
                        class="c-subscriptionCard__icon"
                        large
                        :class="{ 'c-subscriptionCard__icon--hover': hover }"
                    >mdi-chevron-right
                    </v-icon
                    >
                </div>
            </div>
        </v-hover>

        <slot></slot>

        <componentn :is="component" v-if="mode === 'plain'" :data="data" class="text-body-1"></componentn>

        <transition name="expand" mode="out-in">
            <div
                v-if="mode === 'collapse' && open"
                ref="collapsable"
                :class="`pa-${padding}`"
                class="c-subscriptionCard__content expand"
            >
                <component :is="component" :data="data" class="text-body-1"></component>
            </div>
        </transition>

        <v-navigation-drawer
            ref="drawer"
            :value="mode === 'sidebar' && open"
            fixed
            width="450px"
            right
            temporary
            scrollable
            class="text-body-1"
        >
            <v-card tile height="100%">
                <v-toolbar flat color="primary">
                    <v-btn icon @click.stop="open = false">
                        <v-icon class="white--text">mdi-close</v-icon>
                    </v-btn>
                    <v-toolbar-title v-if="title" class="white--text">{{ title }}</v-toolbar-title>
                    <v-spacer></v-spacer>
                    <slot name="button"/>
                </v-toolbar>
                <component :is="component" :class="`pa-${padding} text-body-1`" :data="data"></component>
            </v-card>
        </v-navigation-drawer>
    </v-card>
</template>

<script>
import ShippingDateSetting from '~/components/ShippingDateSetting.vue';
import ChargeSettings from '~/components/ChargeSettings.vue';
import FrequencySettings from '~/components/FrequencySettings.vue';
import PaymentSettings from '~/components/PaymentSettings.vue';
import AddressSettings from '~/components/AddressSettings.vue';

export default {
    components: {
        ShippingDateSetting,
        ChargeSettings,
        FrequencySettings,
        PaymentSettings,
        AddressSettings
    },
    props: {
        title: {
            default: '',
            type: String
        },
        padding: {
            default: 4,
            type: Number
        },
        text: {
            default: '',
            type: String
        },
        html: {
            default: '',
            type: String
        },
        mode: {
            type: String,
            default: 'collapse'
        },
        noIcon: {
            type: Boolean,
            default: false
        },
        textSmall: {
            type: Boolean,
            default: false
        },
        component: {
            type: String,
            default: ''
        },
        data: {
            default: () => {
            },
            type: Object
        }
    },
    data() {
        return {
            open: false
        };
    },
    methods: {
        toggle() {
            if (this.open && this.mode === 'sidebar') {
                const location = this.$refs.drawer.$el.scrollTop;
                window.scroll(0, location);
            }
            this.open = !this.open;
        }
    }
};
</script>

<style lang="scss">
.c-subscriptionCard {
    cursor: pointer;
    border-radius: 0 !important;
    border: 0;

    .c-subscriptionCard__header {
        background-color: #fff;
    }

    .c-subscriptionCard__icon--hover {
        color: var(--v-primary-base) !important;
    }
}

.expand {
    max-height: 1000px;
    animation: slideDown 0.5s linear;
    overflow: hidden;
}

.expand-leave-active.expand-leave-to {
    transition: height 1s ease;
    height: 0;
}

@keyframes slideDown {
    from {
        max-height: 0;
    }
    to {
        height: 450px;
        max-height: 1000px;
    }
}
</style>
