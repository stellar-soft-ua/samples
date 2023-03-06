import btoa from 'btoa';
import request from '~/utils/request.js';

export const state = () => ({
  basicAuth: '',
});

export const getters = {
};

export const mutations = {
  setBasicAuth(state, { email, password }) {
    state.basicAuth = 'Basic ' + btoa(email + ':' + password);
  },

  logout(state) {
    state.basicAuth = false;

    window.$nuxt.$router.replace({ path: '/login' });
  },
};

export const actions = {
  LOGIN_CUSTOMER_BY_LINK({ commit }, {loginLink, storeDomain}) {
    // reset state when logging in a new customer

    return new Promise((resolve, reject) => {
      request({
        method: 'post',
        url: `/customer/magic_login`,
        storeDomain,
        data: {
          auth_token: loginLink
        },
      })
        .then((response) => {
          const token = response.data.data.token;

          // set auth for future requests
          window.localStorage.token = `Bearer ${token}`
          // commit('setBasicAuth', { email, password })
          commit('api/setStoreDomain', storeDomain, { root: true });
          resolve(response);
        })
        .catch((error) => {
          reject(error);
        });
    });
  },

  LOGIN_CUSTOMER({ commit }, { email, token, storeDomain, customer }) {
    // reset state when logging in a new customer

    return new Promise((resolve, reject) => {
      request({
        method: 'post',
        url: `/customer/login`,
        storeDomain,
        data: {
          customer,
          email,
          token,
          name: 'test',
        },
      })
        .then((response) => {
          const token = response.data.data.token;

          // set auth for future requests
          // commit('setBasicAuth', { email, password })
          commit('api/setStoreDomain', storeDomain, { root: true });
          resolve(response);
        })
        .catch((error) => {
          reject(error);
        });
    });
  },
};
