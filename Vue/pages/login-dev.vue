<script lang="ts">
import Vue from 'vue';
import {mapActions} from 'vuex';

export default Vue.extend({
    layout: 'empty',
    data: () => ({
        showPassword: false,

        storeDomain: '',
        customer: '',
        email: '',
        token: '',
        name: 'Customer Portal',
        loading: false
    }),
    mounted() {
        if (this.$route.query.token) {
            this.$store.commit('auth/setUpscribeAccessToken', this.$route.query.token);
            this.$store.commit('api/setStoreDomain', this.$route.query.storeDomain);
            this.$toasted.global
                .success({
                    message: 'Successfully Logged In'
                })
                .goAway(2000);

            this.$router.push({
                name: 'index'
            });
        }
    },
    methods: {
        ...mapActions('auth', ['LOGIN_CUSTOMER']),
        ...mapActions('store', ['GET_STORE']),

        async login() {
            const {email, token, storeDomain, customer} = this;
            if (!email || !token || !storeDomain || !customer) return;

            this.loading = true;
            try {
                const response = await this.LOGIN_CUSTOMER({
                    email,
                    token,
                    customer,
                    storeDomain
                });

                window.localStorage.customerId = response.data.data.customer_id;

                this.$nextTick(async () => {
                    await this.GET_STORE();

                    this.$toasted.global
                        .success({
                            message: 'Successfully Logged In'
                        })
                        .goAway(2000);

                    this.$router.push({
                        name: 'index'
                    });
                });
            } catch (e) {
                console.error('LOGIN_CUSTOMER error: ', e);
                this.$toasted.global.error({
                    message: e.message
                });
            } finally {
                this.loading = false;
            }
        }
    }
});
</script>

<template>
    <v-card width="400" class="mx-auto mt-5">
        <v-card-title>
            <h1 class="display-1">Upscribe</h1>
        </v-card-title>
        <v-card-text>
            <v-form>
                <v-text-field
                    v-model.trim="storeDomain"
                    label="Shopify Store Domain"
                    suffix=".myshopify.com"
                    prepend-icon="mdi-storefront-outline"
                />
                <v-text-field v-model.trim="customer" label="Customer Id"/>
                <v-text-field v-model.trim="email" label="Email" prepend-icon="mdi-account-circle"/>
                <v-text-field
                    v-model.trim="token"
                    :type="showPassword ? 'text' : 'password'"
                    label="Token"
                    prepend-icon="mdi-lock"
                    :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                    @click:append="showPassword = !showPassword"
                />
            </v-form>
        </v-card-text>
        <v-card-actions>
            <v-btn :loading="loading" block @click.prevent="login">LOGIN</v-btn>
            <!-- <v-btn>Forgot Password</v-btn> -->
        </v-card-actions>
    </v-card>
</template>
