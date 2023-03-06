<script>
import { mapMutations } from 'vuex';
import { windowSizes } from '~/mixins/windowSizes';

export default {
	mixins: [windowSizes],

	props: {
		product: {
			type: Object,
			required: true,
		},
		status: {
			type: String,
			default: '',
		},
		isSwap: {
			type: Boolean,
			default: false,
		},
	},
	data: () => {
		return {
			drawerProductsOpen: false,
			quantityControlIsOpen: false,
		};
	},

	methods: {
		...mapMutations('variantSelectProduct', ['setVariantSelectProduct']),

		handleAddProduct() {
			this.setVariantSelectProduct(this.product);
			this.drawerProductsOpen = true;
		},

		quantityChange(type) {
			const { product } = this;
			const quantity = product.quantity;
			if (type === 'decrease') {
				if (quantity === 1) return this.$emit('handleRemove', product);

				this.$emit('handleQuantityChange', {
					quantity: quantity - 1,
					id: product.id,
					variant_id: product.variants[0].id,
				});
			} else if (type === 'increase') {
				this.$emit('handleQuantityChange', {
					quantity: quantity + 1,
					id: product.id,
					variant_id: product.variants[0].id,
				});
			}
			this.closeQuantityControl();
		},
	},
};
</script>

<template>
	<div v-if="product" class="c-productGridItem">
		<div v-if="product" class="c-productGridItem__imageWrap">
			<img
				class="c-productGridItem__image"
				:src="product.variants[0].image"
				:alt="product.title ? product.title : ''"
			/>
		</div>

		<h3
			v-if="product.title"
			class="c-productGridItem__title font-weight-medium"
		>
			{{ product.title }}
		</h3>

		<span v-if="product.price" class="c-productGridItem__price">{{
			formatMoney(product.price)
		}}</span>

		<v-btn
			class="c-productGridItem__button c-button--alt"
			tile
			outlined
			large
			color="primary"
			@click="handleAddProduct"
			>{{ atc('actions.add') }}</v-btn
		>

		<drawer-products
			initial-mode="add"
			:initial-selected-product="product"
			:show="drawerProductsOpen"
			@close="drawerProductsOpen = false"
		/>
	</div>
</template>

<style lang="scss">
.c-productGridItem {
	position: relative;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: flex-start;
	margin-bottom: 42px;
	text-align: center;
}

.c-productGridItem__imageWrap {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 100%;
	max-width: 160px;
	height: 100%;
	margin-bottom: 6px;
}

.c-productGridItem__image {
	display: block;
	width: 100%;
	min-height: 130px;
}

.c-productGridItem__title {
	min-height: 32px;
	margin-bottom: 10px;
	font-size: 14px;
	font-weight: 400;
	line-height: 17px;
	text-align: center;
}

.c-productGridItem__price {
	margin-bottom: 8px;
	font-size: 12px;
	line-height: 16px;
}

.c-productGridItem__button {
	width: auto;
	padding: 12px 20px;
	font-size: 12px;
	font-weight: bold;
	line-height: 16px;
	text-transform: uppercase;
	letter-spacing: 0.8px;
}

.c-productGridItem__button--secondary {
	position: absolute;
	top: 0;
	right: 0;
	width: auto;
	min-width: 0;
	padding: 0;
	border: 0;
}

.c-productGridItem__buttonQuantity {
	min-width: 24px;
	height: 24px;
	border-radius: 50%;
}

.c-productGridItem__quantityControl {
	position: absolute;
	top: 0;
	z-index: 70;
	display: inline-flex;
	width: auto;
	height: 48px;
	border-radius: 4px;
	box-shadow: 0 2px 1px 2px rgba(1, 1, 1, 0.1);
}

.c-productGridItem__quantityBox {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 48px;
	min-width: 0;
	height: 100%;
	border: 0;
}

.c-productGridItem__quantity {
	font-weight: bold;
}

.c-productGridItem__buttonSwap {
	width: auto;
	padding: 12px 20px;
	font-size: 12px;
	font-weight: bold;
	line-height: 16px;
	letter-spacing: 0.8px;
}
</style>
