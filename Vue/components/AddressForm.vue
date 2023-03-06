<script>
import Vue from 'vue';

import {getCountryProvinces, getAllCountryNames} from '~/utils/getCountryData.js';
import {usStateHash} from '~/utils/usStateHash.js';

import 'vue-phone-number-input/dist/vue-phone-number-input.css';
import VuePhoneNumberInput from 'vue-phone-number-input';

export default {
    components: {
        VuePhoneNumberInput
    },

    props: {
        initialAddress: {
            type: Object,
            required: true
        },
        updating: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            usStateHash,
            applyToAllSubscriptions: false,
            phoneNumber: '',
            phoneNumberObject: null,
            defaultPhoneCountry: null,
            phoneInputError: false,
            phoneInputRef: {},

            firstName: '',
            lastName: '',
            company: '',
            address1: '',
            address2: '',
            city: '',
            country: '',
            countryCode: '',
            provinceCode: '',
            zip: '',
            phone: '',

            addressFields: [
                'firstName',
                'lastName',
                'company',
                'address1',
                'address2',
                'city',
                'country',
                'province',
                'zip',
                'phone'
            ],

            phoneNumberTranslations: {
                phoneNumberLabel: `${this.atc('forms.phoneNumberLabel')}`,
            },
        };
    },

    computed: {
        shippingCountryCodes() {
            const {countrySelectOptions} = this;
            if (!countrySelectOptions || !countrySelectOptions.length) return null;
            return countrySelectOptions.map((country) => country.value.code);
        },
        countryCodeNameHash() {
            const countries = getAllCountryNames();

            const countriesHash = {};
            Object.keys(countries).forEach((countryName) => {
                const country = countries[countryName];

                countriesHash[country.code] = {
                    name: country.name,
                    code: country.code
                };
            });
            return countriesHash;
        },

        countrySelectOptions() {
            const countries = getAllCountryNames();

            if (!countries || this.isEmptyObject(countries)) {
                console.warn('countrySelectOptions unavailable');
            }

            const countriesArray = Object.keys(countries).map((countryName) => {
                const country = countries[countryName];
                return {
                    text: countryName,
                    value: country
                };
            });

            return countriesArray.sort(function (a, b) {
                if (a.name < b.name) return -1
                if (a.name > b.name) return 1
                return 0;
            });
        },

        countryProvinces() {
            const {country} = this;

            if (!country || !country.name) return false;

            return getCountryProvinces(country.name);
        },

        provinceSelectOptions() {
            const {countryProvinces} = this;

            if (!countryProvinces) {
                return [];
            }

            return Object.keys(countryProvinces).map((key) => {
                const name = key;
                const code = countryProvinces[key];
                return {
                    value: countryProvinces[key],
                    text: key,
                    payload: {
                        name,
                        code,
                        text: name,
                        value: code
                    }
                };
            });
        },

        address() {
            const {
                firstName,
                lastName,
                company,
                address1,
                address2,
                countryCode,
                provinceCode,
                zip,
                city,
                phone
            } = this;
            const address = {
                first_name: firstName || undefined,
                last_name: lastName || undefined,
                company: company || undefined,
                address1: address1 || undefined,
                address2: address2 || undefined,
                city: city || undefined,
                zip: zip || undefined,
                phone: phone || undefined,
                province_code: provinceCode || undefined,
                country_code: countryCode || undefined
            };

            return address;
        }
    },

    watch: {
        initialAddress: {
            handler: 'updateFullform'
        },

        async country(newCountry, oldCountry) {
            this.countryCode = newCountry.code || 'US';
        }
    },

    mounted() {
        this.updateFullform(true);

        this.phoneInputRef = this.$refs.phoneInput.$refs.PhoneNumberInput.$refs.InputTel;
        this.phoneInputRef.addEventListener('input', this.hanldePhoneInputBlur)
    },

    methods: {
        handlePhoneUpdate(phoneNumberObject) {
            if (!phoneNumberObject) return;

            this.phone = phoneNumberObject.formattedNumber || phoneNumberObject.phoneNumber;

            Vue.set(this, 'phoneNumber', phoneNumberObject.phoneNumber ? phoneNumberObject.phoneNumber : '');
            this.phoneNumberObject = {...phoneNumberObject};
            if (phoneNumberObject) {
                this.defaultPhoneCountry = phoneNumberObject.countryCode;
            }
        },

        setAddressFields(addressFields) {
            const flatAddressFields = Array.prototype.concat.apply([], addressFields);
            this.addressFields = [...flatAddressFields];
        },

        updateFullform(firstLoad) {
            const {initialAddress} = this;

            const preFilledData = initialAddress && !this.isEmptyObject(initialAddress) ? {...initialAddress} : false;

            if (preFilledData) {
                this.firstName = preFilledData.first_name || '';
                this.lastName = preFilledData.last_name || '';
                this.company = preFilledData.company || '';
                this.address1 = preFilledData.address1 || '';
                this.address2 = preFilledData.address2 || '';
                this.city = preFilledData.city || '';
                this.provinceCode = preFilledData.province_code || '';
                this.zip = preFilledData.zip || '';
                this.phone = preFilledData.phone || '';
                this.phoneNumber = preFilledData.phone || '';

                if (preFilledData.country_code) {
                    this.country = {
                        ...this.countryCodeNameHash[preFilledData.country_code]
                    };
                }
            } else {
                this.firstName = '';
                this.lastName = '';
                this.company = '';
                this.address1 = '';
                this.address2 = '';
                this.city = '';
                this.provinceCode = '';
                this.zip = '';
                this.phone = '';
                this.phoneNumber = '';
            }
        },
        hanldePhoneInputBlur() {
            if (!this.phoneNumber) {
                this.phoneInputError = true;
                this.phoneInputRef.classList.add('input-tel__error');
            } else {
                this.phoneInputError = false;
                this.phoneInputRef.classList.remove('input-tel__error');
            }
        },

        submit() {
            this.$refs.observer.validate();
            this.$emit('submitAddress', this.address, this.applyToAllSubscriptions);
        },
        clear() {
            this.$refs.observer.reset();
            this.firstName = '';
            this.lastName = '';
            this.company = '';
            this.address1 = '';
            this.address2 = '';
            this.city = '';
            this.provinceCode = '';
            this.zip = '';
            this.phone = '';
            this.phoneNumber = '';
        }
    }
};
</script>

<template>
    <div>
        <validation-observer ref="observer" v-slot="{ invalid }">
            <form novalidate @submit.prevent="submit">
                <v-checkbox
                    v-if="initialAddress && initialAddress.matching_subscriptions_count"
                    v-model="applyToAllSubscriptions"
                    :label="atc('forms.applyToAllSubscripitons')"
                ></v-checkbox>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.firstNameLabel')} ${atc('notices.requiredError')}`"
                                     rules="required">
                    <v-text-field
                        v-model="firstName"
                        :error-messages="errors"
                        :label="atc('forms.firstNameLabel')"
                        required
                        outlined
                    ></v-text-field>
                </validation-provider>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.lastNameLabel')} ${atc('notices.requiredError')}`"
                                     rules="required">
                    <v-text-field
                        v-model="lastName"
                        :error-messages="errors"
                        :label="atc('forms.lastNameLabel')"
                        required
                        outlined
                    ></v-text-field>
                </validation-provider>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.companyLabel')} ${atc('notices.requiredError')}`">
                    <v-text-field
                        v-model="company"
                        :error-messages="errors"
                        :label="atc('forms.companyLabel')"
                        required
                        outlined
                    ></v-text-field>
                </validation-provider>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.address1Label')} ${atc('notices.requiredError')}`"
                                     rules="required">
                    <v-text-field
                        v-model="address1"
                        :error-messages="errors"
                        :label="atc('forms.address1Label')"
                        required
                        outlined
                    ></v-text-field>
                </validation-provider>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.address2Label')} ${atc('notices.requiredError')}`">
                    <v-text-field
                        v-model="address2"
                        :error-messages="errors"
                        :label="atc('forms.address2Label')"
                        required
                        outlined
                    ></v-text-field>
                </validation-provider>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.countryLabel')} ${atc('notices.requiredError')}`"
                                     rules="required">
                    <v-select
                        v-model="country"
                        :items="countrySelectOptions"
                        :error-messages="errors"
                        :label="atc('forms.countryLabel')"
                        required
                        outlined
                    ></v-select>
                </validation-provider>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.cityLabel')} ${atc('notices.requiredError')}`"
                                     rules="required">
                    <v-text-field
                        v-model="city"
                        :error-messages="errors"
                        :label="atc('forms.cityLabel')"
                        required
                        outlined
                    ></v-text-field>
                </validation-provider>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.provinceLabel')} ${atc('notices.requiredError')}`"
                                     rules="required">
                    <v-select
                        v-model="provinceCode"
                        :items="provinceSelectOptions"
                        :error-messages="errors"
                        :label="atc('forms.provinceLabel')"
                        required
                        outlined
                    ></v-select>
                </validation-provider>

                <validation-provider v-slot="{ errors }"
                                     :name="`${atc('forms.zipcodeLabel')} ${atc('notices.requiredError')}`"
                                     rules="required">
                    <v-text-field
                        v-model="zip"
                        :error-messages="errors"
                        :label="atc('forms.zipcodeLabel')"
                        required
                        outlined
                    ></v-text-field>
                </validation-provider>

                <vue-phone-number-input
                    v-if="shippingCountryCodes"
                    ref="phoneInput"
                    key="address-phone-number-input-1"
                    v-model="phoneNumber"
                    no-example
                    required
                    errors
                    no-flags
                    :default-country-code="defaultPhoneCountry || null"
                    color="#2b2b2b"
                    valid-color="#8A8996"
                    :translations="phoneNumberTranslations"
                    @phone-number-blur="hanldePhoneInputBlur"
                    @update="handlePhoneUpdate"
                />
                <label v-if="phoneInputError" class="error--text caption">
                    {{ atc('forms.phoneNumberLabel') }} {{ atc('notices.requiredError') }}</label
                >

                <div class="my-4 d-flex align-center justify-center">
                    <v-btn v-if="!updating" tile outlined medium @click="clear">
                        {{ atc('actions.clear') }}
                    </v-btn>
                    <v-btn
                        class="ml-4"
                        type="submit"
                        tile
                        medium
                        color="primary"
                        :loading="updating"
                        :disabled="invalid || !phoneNumber"
                        @submit="submit"
                    >
                        {{ atc('actions.submit') }}
                    </v-btn>
                </div>
            </form>
        </validation-observer>
    </div>
</template>

<style lang="scss">
.vue-phone-number-input,
.input-tel[data-v-e59be3b4],
.input-tel__input[data-v-e59be3b4],
.country-selector.has-hint,
.country-selector__input[data-v-46e105de],
.country-selector.has-value .country-selector__input[data-v-46e105de],
.country-selector[data-v-46e105de],
.country-selector__list {
    height: 47px;
}

.input-tel__error {
    border: 2px solid var(--v-error-base) !important;

    &::placeholder {
        color: var(--v-error-base) !important;
    }
}
</style>
