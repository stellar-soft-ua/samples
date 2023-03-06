<script>
export default {
    props: {
        product: {
            type: [Object, Boolean],
            required: true
        }
    }
};
</script>

<template>
    <div class="c-productOption">
        <div v-if="product" class="c-productOption__imageWrap">
            <img
                v-if="product.variant_image"
                class="c-productOption__image"
                :src="product.variant_image"
                :alt="product.title"
            />
        </div>
        <div class="c-productOption__info">
      <span v-if="product.title" class="c-productOption__title">
        {{ product.title }}
        <span v-if="product.variant_title"> ( {{ product.variant_title }} ) </span>
      </span>
            <span class="c-productOption__detail">
        <s v-if="product.base_price && product.base_price !== product.discount_price">{{
                formatMoney(product.base_price)
            }}</s
        ><span>{{ formatMoney(product.discount_price) }}</span>
      </span>

            <strong
                v-if="product.in_stock !== null && product.in_stock !== undefined && !product.in_stock"
                class="c-productOption__detail"
            >
                {{ atc('notices.itemOOSNotInSubtotal') }}
            </strong>

            <span v-if="product && product.price" class="c-productOption__price">{{ formatMoney(product.price) }}</span>
        </div>
    </div>
</template>

<style lang="scss">
.c-productOption {
    display: flex;
    align-items: flex-start;
    justify-content: flex-start;
    margin-bottom: 16px;

    &:last-of-type {
        margin-bottom: 0;
    }
}

.c-productOption__imageWrap {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    min-width: 48px;
    height: 100%;
    margin-right: 10px;
}

.c-productOption__image {
    display: block;
    width: 100%;
    max-width: 100%;
}

.c-productOption__info {
    flex: 1;
    font-size: 11px;
}

.c-productOption__title {
    display: block;
    margin-bottom: 3px;
    font-weight: normal;
    color: #000;
}
</style>
