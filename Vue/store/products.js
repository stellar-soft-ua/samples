import request from '~/utils/request.js';
import { getAvailableCollections, getDefaultActiveCollection } from '~/utils/products';

export const state = () => ({
  currentPage: 0,
  totalCount: 0,
  currentCount: 0,
  defaultLimit: 15,

  products: [],

  variants: []
});

export const mutations = {
  clearProducts(state) {
    state.currentPage = 0;
    state.totalCount = 0;
    state.currentCount = 0;
    state.defaultLimit = 15;

    state.products = [];
  },
  setProducts(state, val) {
    state.products = [...val.data];

    state.totalCount = val.total;
    state.currentPage = val.current_page;
    state.currentCount = val.to;
    state.productsLoaded = true;
  },
  setVariants(state, val) {
    state.variants = [...val.data];
  }
};

export const actions = {
  GET_PRODUCTS({ commit, rootState }, actionPayload = {}) {
    const { storeDomain } = rootState.api;
    const { activeCollection, collections } = rootState.collections;
    const { cp_disable_product_all_tab, cp_subscription_product_collections } = rootState.store.storeSettings;

    const availableCollections = getAvailableCollections({
      collections,
      // editNextOrder: undefined
      customerPortalSubscriptionProductCollections: cp_subscription_product_collections
      // customerPortalNextOrderProductCollections: undefined
    });
    const defaultActiveCollection = getDefaultActiveCollection({
      cp_disable_product_all_tab,
      availableCollections
    });

    commit('collections/setAvailableCollections', availableCollections, {
      root: true
    });
    commit('collections/setDefaultActiveCollection', defaultActiveCollection, {
      root: true
    });

    if (!activeCollection || !activeCollection.id) {
      commit('collections/setActiveCollection', defaultActiveCollection, {
        root: true
      });
    }

    const { baseStateLoad, collectionId } = actionPayload;

    let url = '';
    if (baseStateLoad) {
      if (!defaultActiveCollection) {
        url = `/products`;
      } else {
        url = `/collections/${defaultActiveCollection.id}/products`;
      }
    } else if (collectionId) {
      url = `/collections/${collectionId}/products`;
    } else {
      if (activeCollection) {
        url = `/collections/${defaultActiveCollection.id}/products`;
      } else {
        url = `/products`;
      }
    }

    commit('clearProducts');

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url
      })
        .then((response) => {
          commit('setProducts', response.data);
          resolve(response.data);
        })
        .catch((error) => {
          console.error('LIST_PRODUCTS error: ', error);
          reject(error);
        });
    });
  },

  LIST_PRODUCTS({ commit, rootState }, payload = {}) {
    const { storeDomain } = rootState.api;
    const params = payload.params || {};

    commit('clearProducts');

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/products`,
        params
      })
        .then((response) => {
          commit('setProducts', response.data);
          resolve(response.data);
        })
        .catch((error) => {
          console.error('LIST_PRODUCTS error: ', error);
          reject(error);
        });
    });
  },

  LIST_COLLECTION_PRODUCTS({ rootState, commit }, { id, requestPayload }) {
    const { storeDomain } = rootState.api;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/collections/${id}/products`,
      })
        .then((response) => {
          commit('setProducts', response.data);
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`LIST_COLLECTION_PRODUCTS ${id} error: `, error);
          reject(error);
        });
    });
  },

  LIST_SUBSCRIPTION_ITEM_PRODUCTS({ rootState, commit }, { subId, itemId, requestPayload }) {
    const { storeDomain } = rootState.api;

    // const page = 1 || requestPayload.page;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/subscriptions/${subId}/items/${itemId}/swappable/products`,
      })
        .then((response) => {
          commit('setProducts', response.data);
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`LIST_SUBSCRIPTION_ITEM_PRODUCTS error: `, error);
          reject(error);
        });
    });
  },

  LIST_VARIANTS({ commit, rootState }) {
    const { storeDomain } = rootState.api;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/variants`,
      })
        .then((response) => {
          commit('setVariants', response.data);
          resolve(response.data);
        })
        .catch((error) => {
          console.error('LIST_VARIANTS error: ', error);
          reject(error);
        });
    });
  },

  GET_PRODUCT_BY_ID({ rootState }, id) {
    const { storeDomain } = rootState.api;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/products/${id}`,
      })
        .then((response) => {
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`GET_PRODUCT_BY_ID ${id} error: `, error);
          reject(error);
        });
    });
  },

  GET_PRODUCT_VARIANTS({ rootState }, id) {
    const { storeDomain } = rootState.api;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/products/${id}/variants`
      })
        .then((response) => {
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`GET_PRODUCT_VARIANTS ${id} error: `, error);
          reject(error);
        });
    });
  },

  GET_VARIANT_BY_IDS({ rootState }, { productId, variantId }) {
    const { storeDomain } = rootState.api;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/product/${productId}/variants/${variantId}`
      })
        .then((response) => {
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`GET_VARIANT_BY_IDS. Product Id: ${productId} / Variant Id: ${variantId}. error: `, error);
          reject(error);
        });
    });
  }
};
