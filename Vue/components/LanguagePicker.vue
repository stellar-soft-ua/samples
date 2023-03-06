<script>
/* eslint-disable */

import { mapActions, mapMutations } from 'vuex';

import languageCodes from '~/utils/languageCodes';

export default {
	components: {},

	props: [
		'translationList',
		'translations',
		'activeLanguageCode',
		'activeLanguageName',
		'activeLanguageNativeName',
	],

	data: () => {
		return {
			dropdownOpen: false,
			languageCodes,
			activeLanguageLocal: null,
		};
	},

	watch: {
		async activeLanguageLocal(newLanguageCode) {
			// if language not alread loaded, load
			if (newLanguageCode && !this.translations[newLanguageCode]) {
				await this.TRANSLATION_GET({ code: newLanguageCode });
			}

			// then set active language
			this.$nextTick(() => {
				this.setActiveLanguageCode(newLanguageCode);
			});
		},
	},

	created() {
		this.activeLanguageLocal = this.activeLanguageCode;
	},

	methods: {
		...mapMutations('translations', ['setActiveLanguageCode']),

		...mapActions('translations', ['TRANSLATION_GET']),

		async switchLanguage(event) {
			const { translations } = this;
			const languageCode = event.target.value;

			// if language not already loaded, load
			if (!translations[languageCode]) {
				await this.TRANSLATION_GET({ language: languageCode });
			}

			// then set active language
			this.$nextTick(() => {
				this.setActiveLanguageCode(languageCode);
			});
		},
	},
};
</script>

<template>
	<v-select
		v-if="translationList && translationList.length > 1"
		v-model="activeLanguageLocal"
		:label="atc('labels.chooseLanguage')"
		outlined
		hide-details
		style="width: 300px; margin-left: auto"
		:items="
			translationList.map((langugageCode) => {
				return {
					text: languageCodes[langugageCode].nativeName,
					value: langugageCode,
				};
			})
		"
	/>
</template>