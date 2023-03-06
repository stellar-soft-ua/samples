import request from '~/utils/request.js';

export const actions = {
  GET_ADDRESS({ rootState }, id) {
    const { storeDomain } = rootState.api;
    const customerId = rootState?.customers?.customer?.id;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/customers/${customerId}/addresses`,
      })
        .then((response) => {
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`CHARGE_GET ${id} error: `, error);
          reject(error);
        });
    });
  },
  GET_ADDRESS_BY_ID({ rootState }, id) {
    const { storeDomain } = rootState.api;
    const customerId = rootState?.customers?.customer?.id;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'get',
        url: `/customers/${customerId}/addresses/${id}`,
      })
        .then((response) => {
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`CHARGE_GET ${id} error: `, error);
          reject(error);
        });
    });
  },
  CREATE_ADDRESS({ rootState }, payload) {
    const { storeDomain } = rootState.api;
    const customerId = rootState?.customers?.customer?.id;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'post',
        url: `/customers/${customerId}/addresses`,
        data: {
          ...payload,
        },
      })
        .then((response) => {
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`CHARGE_GET ${payload.id} error: `, error);
          reject(error);
        });
    });
  },
  UPDATE_ADDRESS({ rootState }, { id, payload, applyToAllSubscriptions }) {
    const { storeDomain } = rootState.api;
    const customerId = rootState?.customers?.customer?.id;

    const url = applyToAllSubscriptions
      ? `/customers/${customerId}/addresses/${id}/subscriptions`
      : `/customers/${customerId}/addresses/${id}`;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'put',
        url,
        data: {
          ...payload,
        },
      })
        .then((response) => {
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`CHARGE_GET ${payload.id} error: `, error);
          reject(error);
        });
    });
  },
  DELETE_ADDRESS({ rootState }, id) {
    const { storeDomain } = rootState.api;
    const customerId = rootState?.customers?.customer?.id;

    return new Promise((resolve, reject) => {
      request({
        storeDomain,
        method: 'delete',
        url: `/customers/${customerId}/addresses/${id}`
      })
        .then((response) => {
          resolve(response.data);
        })
        .catch((error) => {
          console.error(`CHARGE_GET ${id} error: `, error);
          reject(error);
        });
    });
  },
};
