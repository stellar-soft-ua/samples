<script>
export default {
	props: {
		title: {
			type: [String, Boolean],
			default: false,
		},
		text: {
			type: [String, Boolean],
			default: false,
		},
		html: {
			type: [String, Boolean],
			default: false,
		},
		textLarge: {
			type: Boolean,
			default: false,
		},
		textMed: {
			type: Boolean,
			default: false,
		},
		titleLight: {
			type: Boolean,
			default: false,
		},
		noAction: {
			type: Boolean,
			default: false,
		},
	},
};
</script>

<template>
	<v-card-text class="c-subscriptionBlockOption">
		<h3
			v-if="title"
			class="c-subscriptionBlockOption__title mb-2 text-overline"
			:class="{ 'c-subscriptionBlockOption__title--light': titleLight }"
		>
			{{ title }}
		</h3>
		<span
			v-if="text"
			class="c-subscriptionBlockOption__text grey--text text--darken-4"
			:class="{
				'c-subscriptionBlockOption__text--large': textLarge,
				'text-h4': textLarge,
				'c-subscriptionBlockOption__text--med': textMed,
			}"
			>{{ text }}</span
		>
		<!-- eslint-disable -->
		<div
			v-if="html"
			class="c-subscriptionBlockOption__text ma-0"
			v-html="html"
			:class="{
				'c-subscriptionBlockOption__text--large': textLarge,
				'c-subscriptionBlockOption__text--med': textMed,
			}"
		></div>
		<!-- eslint-enable -->

		<v-icon v-if="!noAction" class="c-subscriptionBlockOption__icon" large
			>mdi-chevron-right</v-icon
		>

		<div v-if="$slots.default" class="c-subscriptionBlockOption__inner">
			<slot />
		</div>
	</v-card-text>
</template>

<style lang="scss">
.c-subscriptionBlockOption__icon {
	position: absolute !important;
	right: 12px;
	top: 50%;
	transform: translateY(-50%);
	transition: all 0.35s ease;
}

.c-subscriptionBlockOption {
	position: relative;

	&:hover {
		.c-subscriptionBlockOption__icon {
			right: 0;
		}
	}
}
</style>
